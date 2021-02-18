<?php

namespace App\Services\NetSuite\Customer;

use App\Services\NetSuite\Service;
use App\Services\NetSuite\CustomList\{
    BusinessModel,
    AccountCategory,
    Territory,
    CustomerStatus
};

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
};

class SavedSearch extends Service {

    const PER_PAGE = 1000;
    const NETSUITE_SAVED_SEARCH_ID = 'customsearch_sm_badgermaps';

    const ACCOUNT_CATEGORY = 'custentity_sm_accountcategory';
    const LICENSE_REQUIRED = 'custentity_sm_licenserequiredforsale';
    const BUSINESS_MODEL = 'custentity_sm_businessmodel';
    const CUSTOMFIELDS = [
        'custentity_sm_licenserequiredforsale' => 'license_required',
        'custentity_bg_taxid' => 'bg_tax_number',
        'custentity_sm_accountcategory' => 'account_category',
        'custentity_sm_businessmodel' => 'business_model'
    ];



    private $request;
    private $previousSearchId;
    private $totalPages;
    private $businessModels;
    private $accountCategories;
    private $territories;
    private $customerStatuses;

    public function __construct(BusinessModel $businessModel, AccountCategory $accountCategory, Territory $territory, CustomerStatus $customerStatus)
    {
        parent::__construct();
        $this->setSavedSearchScriptId();
        $this->setSearchCriteria();
        $this->businessModels = $businessModel->getRequest();
        $this->accountCategories = $accountCategory->getRequest();
        $this->territories = $territory->getRequest();
        $this->customerStatuses = $customerStatus->getRequest();
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
        $customFields = $this->processCustomFieldList($result->basic->customFieldList->customField);
        $fields = [
            'nsid' => $result->basic->internalId[0]->searchValue->internalId,
            'company_name' => $result->basic->companyName ? $result->basic->companyName[0]->searchValue : '',
            'sale_rep' => $result->salesRepJoin->email ? $result->salesRepJoin->email[0]->searchValue : '',
            'status' => $result->basic->entityStatus ? 
                        (array_key_exists($result->basic->entityStatus[0]->searchValue->internalId ,$this->customerStatuses) ? $this->customerStatuses[$result->basic->entityStatus[0]->searchValue->internalId] : '')
                         : '',
            'territory' => $result->basic->territory ? $this->territories[$result->basic->territory[0]->searchValue->internalId] : '',
            'shipping_address1' => $result->basic->shipAddress1 ? $result->basic->shipAddress1[0]->searchValue : '',
            'shipping_address2' => $result->basic->shipAddress2 ? $result->basic->shipAddress2[0]->searchValue : '',
            'shipping_city' => $result->basic->shipCity ? $result->basic->shipCity[0]->searchValue : '',
            'shipping_country' => $result->basic->shipCountry ? $result->basic->shipCountry[0]->searchValue : '',
            'shipping_zip' => $result->basic->shipZip ? $result->basic->shipZip[0]->searchValue : '',
            'primary_contact' => $result->basic->contact ? $result->basic->contact[0]->searchValue : '',
            'alt_contact' => $result->basic->altContact ? $result->basic->altContact[0]->searchValue : '',
            'phone' => $result->basic->phone ? $result->basic->phone[0]->searchValue : '',
            'office_phone' => $result->basic->altPhone ? $result->basic->altPhone[0]->searchValue : '',
            'email' => $result->basic->email ? $result->basic->email[0]->searchValue : '',
            'fax' => $result->basic->fax ? $result->basic->fax[0]->searchValue : '',
            'billing_address1' => $result->basic->billAddress1 ? $result->basic->billAddress1[0]->searchValue : '',
            'billing_address2' => $result->basic->billAddress2 ? $result->basic->billAddress2[0]->searchValue : '',
            'billing_city' => $result->basic->billCity ? $result->basic->billCity[0]->searchValue : '',
            'billing_state' => $result->basic->billState ? $result->basic->billState[0]->searchValue : '',
            'billing_zip' => $result->basic->billZipCode ? $result->basic->billZipCode[0]->searchValue : '',
            'billing_country' => $result->basic->billCountry ? $result->basic->billCountry[0]->searchValue : '',
        ];
        return array_merge($fields, $customFields);
    }

    private function processCustomFieldList($fields)
    {
        $res = [];
        foreach ($fields as $field) {
            if(array_key_exists($field->scriptId, self::CUSTOMFIELDS)){
                if($field->scriptId == self::LICENSE_REQUIRED){
                    $res[self::CUSTOMFIELDS[$field->scriptId]] = $field->searchValue == 'true' ? 'Yes' : 'No';
                }
                elseif ($field->scriptId == self::ACCOUNT_CATEGORY) {
                    $res[self::CUSTOMFIELDS[$field->scriptId]] = $this->accountCategories[$field->searchValue->internalId];
                }
                elseif ($field->scriptId == self::BUSINESS_MODEL) {
                    $res[self::CUSTOMFIELDS[$field->scriptId]] = $this->processBusinessModel($field->searchValue);
                }
                else{
                    $res[self::CUSTOMFIELDS[$field->scriptId]] = $field->searchValue;
                }
            }
        }
        return $res;
    }

    private function processBusinessModel($businessModel)
    {
        $string = '';
        foreach ($businessModel as $bm) {
            $string .= $this->businessModels[$bm->internalId].'/';
        }
        return $string;
    }
}
