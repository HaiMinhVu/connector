<?php

namespace App\Services\NetSuite\Customer;

use Illuminate\Support\Facades\Cache;
use App\Services\NetSuite\Service;
use App\Models\{
    CustEntity,
    SalesRep
};
use Carbon\Carbon;
use NetSuite\Classes\{
    CustomerSearch,
    CustomerSearchAdvanced,
    CustomerSearchBasic,
    SearchDateField,
    SearchDateFieldOperator,
    SearchRequest,
    SearchMoreWithIdRequest
};

class SavedSearch extends Service {

    const PER_PAGE = 100;
    const NETSUITE_SAVED_SEARCH_ID = 'customsearch_badger_sync';

    private $request;
    private $previousSearchId;
    private $totalPages;

    public function __construct()
    {
        parent::__construct();
        $this->setSavedSearchScriptId();
    }

    public function setFromDate($dateString)
    {
        $fromDate = Carbon::parse($dateString)->toAtomString();
        $this->search->criteria = new CustomerSearch();
		$this->search->criteria->basic = new CustomerSearchBasic();
		$this->search->criteria->basic->lastModifiedDate = new SearchDateField;
		$this->search->criteria->basic->lastModifiedDate->operator = SearchDateFieldOperator::onOrAfter;
		$this->search->criteria->basic->lastModifiedDate->searchValue = $fromDate;
    }

    public function getTotalPages()
    {
        return $this->totalPages;
    }

    public function setSavedSearchScriptId()
    {
        $this->search = new CustomerSearchAdvanced();
        $this->search->savedSearchScriptId = self::NETSUITE_SAVED_SEARCH_ID;
        $this->service->setSearchPreferences(false, self::PER_PAGE);
    }

    public function setInitialRequest()
    {
        $this->request = new SearchRequest();
        $this->request->searchRecord = $this->search;
    }

    public function setPaginatedRequest($page = 1)
    {
        $this->request = new SearchMoreWithIdRequest();
        $this->request->searchId = $this->previousSearchId;
        $this->request->pageIndex = $page;
    }

    public function search($page = 1)
    {
        if($page == 1) {
            $this->setInitialRequest();
            $response = $this->service->search($this->request);
        } else {
            $response = Cache::remember($this->getCacheId($page), self::CACHE_SECONDS, function() use ($page) {
                $this->setPaginatedRequest($page);
                return $this->service->searchMoreWithId($this->request);
            });
        }

        $this->totalPages = $response->searchResult->totalPages;
        $this->previousSearchId = $response->searchResult->searchId;

        $results = collect($response->searchResult->searchRowList->searchRow);
        return $this->appendCustomerRecords($results);
    }

    private function getCacheId($page = 1)
    {
        $savedSearchScriptId = self::NETSUITE_SAVED_SEARCH_ID;
        $perPage = self::PER_PAGE;
        return "search_{$savedSearchScriptId}_{$page}_{$perPage}";
    }

    private function filterResults($results)
    {
        return $results->filter(function($item){
            $hasAddress = !!$item->basic->address;
            $hasSalesRep = !!$item->basic->salesRep;
            $hasContact = !!$item->basic->contact;
            $isDefaultShippingAddress = $item->basic->isDefaultShipping[0]->searchValue;

            return $hasAddress && $hasSalesRep && $hasContact && $isDefaultShippingAddress;
        });
    }

    private function appendCustomerRecords($results)
    {
        $filteredResults = $this->filterResults($results);

        return $filteredResults->map(function($result) {
            return $this->formatRawData($result);
        });
    }

    public function formatRawData($result)
    {
        $nsid = $result->basic->internalId[0]->searchValue->internalId;
        $data = [
            'customer' => $result,
            'records' => $this->getCustomerRecords($nsid)
        ];

        return $this->parseData($data);
    }

    public function getCustomerRecords($nsid)
    {
        try {
            $record = new Record();
            $response = $record->getByNSID($nsid);
        } catch(\Exception $e) {
            dd($e->getMessage());
        }

        $response->readResponse->record->customFieldList->customField = collect($response->readResponse->record->customFieldList->customField)->mapWithKeys(function($field){
            $key = CustEntity::getDescById($field->scriptId);
            $value = $field->value;
            if(is_object($value)) {
                $value = $value->name;
            }
            if(is_array($value)) {
                $value = collect($value)->map(function($item){
                    return $item->name;
                });
            }
            return [$key => $value];
        });

        return $response;
    }

    private function parseData($data) : array
    {
        $customer = $data['customer'];
        $basic = $data['customer']->basic;
        $records = $data['records']->readResponse->record;
        $salesRep = SalesRep::where('nsid', $basic->salesRep[0]->searchValue->internalId)->first();
        $type = optional($records->customFieldList->customField->get('Business Model'));
        $isPerson = $type ? ($type->first() == 'Individual' ? 1 : 0) : 0;

        return [
            "_Name" => $records->companyName,
            "_Address" => str_replace(["\n","\r\n","\r"], " ", $basic->address[0]->searchValue),
            "_Phone" => preg_replace('/[^0-9]/', '', $records->phone),
            "nsid" => $records->internalId,
            "_AccountOwner" => optional($salesRep)->email,
            "Business Email" => $basic->email ? $basic->email[0]->searchValue : '',
            "Contact Name" => $records->contactRolesList->contactRoles[0]->contact->name,
            "Contact Email" => $records->contactRolesList->contactRoles[0]->email,
            'is Person' => $isPerson,
            "Status" => $records->entityStatus->name,
            "url" => $records->url,
            "category" => $records->customFieldList->customField->get('Account Category'),
            "territory" => optional($records->territory)->name,
            "lastModifiedDate" => $records->lastModifiedDate
        ];
    }

}
