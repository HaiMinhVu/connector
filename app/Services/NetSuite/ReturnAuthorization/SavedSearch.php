<?php

namespace App\Services\NetSuite\ReturnAuthorization;

use App\Services\NetSuite\Service;
use NetSuite\Classes\{
    
    TransactionSearch,
    TransactionSearchBasic,
    TransactionSearchAdvanced,
    SearchRequest,
    SearchDateField,
    SearchDateFieldOperator,
    SearchMoreWithIdRequest
};
use Carbon\Carbon;

class SavedSearch extends Service {

    const PER_PAGE = 1000;
    const NETSUITE_SAVED_SEARCH_ID = 'customsearch_racm';

    private $request;
    private $search;
    private $totalPages;
    private $previousSearchId;

    public function __construct()
    {
        parent::__construct();
        $this->setSavedSearchScriptId();
        $this->setSearchCriteria();
    }

    public function getTotalPages()
    {
        return $this->totalPages;
    }

    private function setSearchCriteria()
    {
        $this->search->criteria = new TransactionSearch();
        $this->search->criteria->basic = new TransactionSearchBasic();
    }

    public function setSavedSearchScriptId()
    {
        $this->search = new TransactionSearchAdvanced();
        $this->search->savedSearchScriptId = self::NETSUITE_SAVED_SEARCH_ID;
        $this->service->setSearchPreferences(false, self::PER_PAGE);
    }

    public function setFromDate($dateString)
    {
        $fromDate = Carbon::parse($dateString)->toAtomString();
        $this->search->criteria->basic->lastModifiedDate = new SearchDateField;
        $this->search->criteria->basic->lastModifiedDate->operator = SearchDateFieldOperator::onOrAfter;
        $this->search->criteria->basic->lastModifiedDate->searchValue = $fromDate;
    }

    public function setPaginatedRequest($page = 1)
    {
        $this->request = new SearchMoreWithIdRequest();
        $this->request->searchId = $this->previousSearchId;
        $this->request->pageIndex = $page;
    }

    public function setInitialRequest()
    {
        $this->request = new SearchRequest();
        $this->request->searchRecord = $this->search;
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
        return $results->map(function($result){
            return $this->parseResult($result);
        });
    }

    private function parseResult($result)
    {
        return [
            'documentNumber' => $result->basic->tranId[0]->searchValue,
            'type' => $result->basic->type[0]->searchValue,
            'customer' => $result->basic->entity[0]->searchValue->internalId,
            'item' => $result->itemJoin->itemId[0]->searchValue,
            'description' => $result->basic->memo[0]->searchValue,
            'quantity' => $result->basic->quantity ? $result->basic->quantity[0]->searchValue : 0,
            'lastModifiedDate' => $result->basic->lastModifiedDate[0]->searchValue,
        ];
    }


}
