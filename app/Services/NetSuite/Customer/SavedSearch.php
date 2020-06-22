<?php

namespace App\Services\NetSuite\Customer;

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
    SearchMoreWithIdRequest,
    SearchBooleanField
};

class SavedSearch extends Service {

    const PER_PAGE = 1000;
    const NETSUITE_SAVED_SEARCH_ID = 'customsearch_badger_sync';

    private $request;
    private $previousSearchId;
    private $totalPages;

    public function __construct()
    {
        parent::__construct();
        $this->setSavedSearchScriptId();
        $this->setSearchCriteria();
    }

    public function setFromDate($dateString)
    {
        $fromDate = Carbon::parse($dateString)->toAtomString();

		$this->search->criteria->basic->lastModifiedDate = new SearchDateField;
		$this->search->criteria->basic->lastModifiedDate->operator = SearchDateFieldOperator::onOrAfter;
		$this->search->criteria->basic->lastModifiedDate->searchValue = $fromDate;
    }

    private function setSearchCriteria()
    {
        $this->search->criteria = new CustomerSearch();
		$this->search->criteria->basic = new CustomerSearchBasic();
        $this->search->criteria->basic->isPerson = null;
        $this->search->criteria->basic->isDefaultShipping = new SearchBooleanField;
        $this->search->criteria->basic->isDefaultShipping->searchValue = true;
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
            $this->setPaginatedRequest($page);
            $response = $this->service->searchMoreWithId($this->request);
        }

        $this->totalPages = $response->searchResult->totalPages;
        $this->previousSearchId = $response->searchResult->searchId;

        $results = collect($response->searchResult->searchRowList->searchRow);
        return $this->filterResults($results)->map(function($result){
            return $this->parseInitialResult($result);
        });
    }

    private function filterResults($results)
    {
        return $results->filter(function($item){
            $hasAddress = !!$item->basic->address;
            $hasSalesRep = !!$item->basic->salesRep;
            $isDefaultShippingAddress = $item->basic->isDefaultShipping[0]->searchValue;

            return $hasAddress && $hasSalesRep && $isDefaultShippingAddress;
        });
    }

    private function parseInitialResult($result)
    {
        return [
            'nsid' => $result->basic->internalId[0]->searchValue->internalId,
            "_Address" => str_replace(["\n","\r\n","\r"], " ", $result->basic->address[0]->searchValue),
            "Business Email" => $result->basic->email ? $result->basic->email[0]->searchValue : '',
            "SalesRepNSID" => $result->basic->salesRep[0]->searchValue->internalId
        ];
    }

    public static function getCustomerRecords($nsid)
    {
        try {
            $record = new Record();
            $response = $record->getByNSID($nsid);
        } catch(\Exception $e) {
            // dd($e->getMessage());
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

    private static function getContact(?object $contactRoleList) : ?object
    {
        if($contactRoleList) {
          $firstContact = $contactRoleList->contactRoles[0];
          return (object)[
            'name' => $firstContact->contact->name,
            'email' => $firstContact->email
          ];
        }
        return null;
    }

    public static function getRecords($data) : array
    {
        $nsid = $data['nsid'];
        $response = self::getCustomerRecords($nsid);
        $records = $response->readResponse->record;
        $salesRep = SalesRep::where('nsid', $data['SalesRepNSID'])->first();
        $type = optional($records->customFieldList->customField->get('Business Model'));
        $isPerson = $type ? ($type->first() == 'Individual' ? 1 : 0) : 0;
        $contact = optional(self::getContact($records->contactRolesList));

        return [
            "_Name" => $records->companyName,
            "_Phone" => preg_replace('/[^0-9]/', '', $records->phone),
            "_AccountOwner" => optional($salesRep)->email,
            "Contact Name" => $contact->name ?? '',
            "Contact Email" => $contact->email ?? '',
            'is Person' => $isPerson,
            "Status" => $records->entityStatus->name,
            "url" => $records->url,
            "category" => $records->customFieldList->customField->get('Account Category'),
            "territory" => optional($records->territory)->name,
            "lastModifiedDate" => $records->lastModifiedDate
        ];
    }
}
