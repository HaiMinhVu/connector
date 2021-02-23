<?php

namespace App\Services\NetSuite\SalesRep;

use Illuminate\Support\Facades\Cache;
use App\Services\NetSuite\Service;
use App\Models\{
    CustEntity,
    SalesRep
};
use Carbon\Carbon;
use NetSuite\Classes\{
    EmployeeSearchAdvanced,
    EmployeeSearchRow,
    EmployeeSearchRowBasic,
    SearchColumnBooleanField,
    SearchColumnDateField,
    SearchColumnSelectField,
    SearchColumnStringField,
    SearchRequest
};

class SavedSearch extends Service {

    const PER_PAGE = 200;
    const NETSUITE_SAVED_SEARCH_ID = 'customsearch_sm_salesroles';

    private $request;

    public function __construct()
    {
        parent::__construct();
        $this->init();
    }

    public function init()
    {
        $this->search = new EmployeeSearchAdvanced();
        $this->search->savedSearchScriptId = self::NETSUITE_SAVED_SEARCH_ID;

        $this->service->setSearchPreferences(true, self::PER_PAGE, true);
        $this->search->columns = new EmployeeSearchRow();

        $this->search->columns->basic = new EmployeeSearchRowBasic();
        $this->search->columns->basic->lastModifiedDate = new SearchColumnDateField;
        $this->search->columns->basic->internalId = new SearchColumnSelectField;
        $this->search->columns->basic->firstName = new SearchColumnStringField;
        $this->search->columns->basic->lastName = new SearchColumnStringField;
        $this->search->columns->basic->isInactive = new SearchColumnBooleanField;
        $this->search->columns->basic->entityId = new SearchColumnStringField;
        $this->search->columns->basic->email = new SearchColumnStringField;
    }

    public function setRequest()
    {
        $this->request = new SearchRequest();
        $this->request->searchRecord = $this->search;
    }

    public function search()
    {
        $this->setRequest();
        $response = $this->service->search($this->request);
        $results = collect($response->searchResult->searchRowList->searchRow);
        return $this->parse($results);
    }

    private function parse($results)
    {
        return $results->map(function($result) {
            return $this->parseData($result);
        });
    }

    private function parseData($data) : array
    {
        $basic = $data->basic;
        $isActive = !$basic->isInactive[0]->searchValue;
        return [
            "email" => $basic->email[0]->searchValue,
            "lastModifiedDate" => $basic->lastModifiedDate[0]->searchValue,
            "active" => $isActive,
            "name" => $basic->entityId[0]->searchValue,
            "nsid" => $basic->internalId[0]->searchValue->internalId
        ];
    }

}
