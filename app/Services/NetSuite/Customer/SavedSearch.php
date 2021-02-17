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
    CustomerSearchRow,
    CustomerSearchRowBasic,
    SearchDateField,
    SearchDateFieldOperator,
    SearchRequest,
    SearchMoreWithIdRequest,
    SearchBooleanField,
};

class SavedSearch extends Service {

    const PER_PAGE = 10;
    const NETSUITE_SAVED_SEARCH_ID = 'customsearch_sm_badgermaps';
    const TYPE = [
        1 => 'I',
        2 => 'D',
        3 => 'U'
    ];

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
        // return $results;
        return $this->filterResults($results)->map(function($result){
            return $this->parseInitialResult($result);
        });
    }

    private function filterResults($results)
    {
        return $results->filter(function($item){
            $hasCompanyName = !!$item->basic->companyName;
            $hasNSID = !!$item->basic->internalId[0];
            return $hasCompanyName && $hasNSID;
        });
    }

    private function parseInitialResult($result)
    {
        return [
            'nsid' => $result->basic->internalId[0]->searchValue->internalId,
            'company_name' => $result->basic->companyName ? $result->basic->companyName[0]->searchValue : '',
            'sale_rep' => $result->salesRepJoin->email ? $result->salesRepJoin->email[0]->searchValue : '',
            'status' => $result->basic->entityStatus ? $result->basic->entityStatus[0]->searchValue->internalId : '',
            'territory' => $result->basic->territory ? $result->basic->territory[0]->searchValue->internalId : '',
            'shipping_address1' => $result->basic->shipAddress1 ? $result->basic->shipAddress1[0]->searchValue : '',
            // 'shipping_address2' => $this->shipping_address2,
            'shipping_city' => $result->basic->shipCity ? $result->basic->shipCity[0]->searchValue : '',
            'shipping_country' => $result->basic->shipCountry ? $result->basic->shipCountry[0]->searchValue : '',
            'shipping_zip' => $result->basic->shipZip ? $result->basic->shipZip[0]->searchValue : '',
            // 'primary_contact' => $this->primary_contact,
            'phone' => $result->basic->phone ? $result->basic->phone[0]->searchValue : '',
            'email' => $result->basic->email ? $result->basic->email[0]->searchValue : '',
            // 'fax' => $this->fax,
            // 'alt_contact' => $this->alt_contact,
            // 'office_phone' => $this->office_phone,
            'license_required' => $result->basic->customFieldList->customField[2]->searchValue == 'true' ? 'Yes' : 'No',
            'billing_address1' => $result->basic->billAddress1 ? $result->basic->billAddress1[0]->searchValue : '',
            // 'billing_address2' => $this->billing_address2,
            'billing_city' => $result->basic->billCity ? $result->basic->billCity[0]->searchValue : '',
            // 'billing_state' => $this->billing_state,
            'billing_zip' => $result->basic->billZipCode ? $result->basic->billZipCode[0]->searchValue : '',
            'billing_country' => $result->basic->billCountry ? $result->basic->billCountry[0]->searchValue : '',
            // 'account_category' => $this->account_category,
            'bg_tax_number' => $result->basic->customFieldList->customField[1]->searchValue,
            // 'business_model' => $this->business_model,
            'change_type' => isset($result->basic->customFieldList->customField[0]->searchValue->internalId) ? self::TYPE[$result->basic->customFieldList->customField[0]->searchValue->internalId] : 'U'
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
