<?php

namespace App\Services\NetSuite\Inventory;

use Illuminate\Support\Facades\Cache;
use App\Services\NetSuite\Service;
use NetSuite\Classes\{
    SearchBooleanField,
    ItemSearchBasic,
    SearchRequest,
    SearchMoreWithIdRequest,
    SearchDateField,
    SearchDateFieldOperator,
    RecordRef
};
use Closure;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\NetsuiteProduct;

class Search extends Service {

    const FROM_DATE = '2015-01-01';
    const PER_PAGE = 200;
    const CUSTOM_FIELD_MAP = [
        ['label' => 'eccn', 'id' => 1],
        ['label' => 'end_date', 'id' => 6],
        ['label' => 'netsuite_category', 'id' => 2],
        ['label' => 'ccats', 'id' => 10],
        ['label' => 'product_sizing', 'id' => 9],
        ['label' => 'start_date', 'id' => 5]
    ];

    protected $previousSearchId;
    protected $totalPages = null;
    protected $search;
    protected $request;
    protected $response;
    protected $page = 1;

    public function __construct()
    {
        parent::__construct();
        $this->init();
    }

    public function search(Closure $callback) {

        do {
            $records = $this->searchAction();
            $callback($records);
        } while($this->inLoop());
    }

    protected function searchAction()
    {
        $perPage = self::PER_PAGE;
        $response = $this->runSearch();
        return $this->handleResponse($response);
    }

    private function runSearch()
    {
	   try {
       	    if($this->page > 1) {
                return $this->paginatedSearch($this->request);
            } else {
                return $this->initialSearch($this->request);
            }
	   } catch(\Exception $e) {
	       $this->runSearch();
	   }
    }

    private function getCacheId()
    {
        $perPage = self::PER_PAGE;
        return "inventory_search_{$perPage}_{$this->page}";
    }

    public function initialSearch()
    {
        return $this->service->search($this->request);
    }

    public function paginatedSearch() 
    {
        if($this->inLoop()) {
            $this->setPaginatedRequest();
            return $this->service->searchMoreWithId($this->request);
        }
    }

    protected function handleResponse($response)
    {
        $searchResult = $response->searchResult;
        if($searchResult->status->isSuccess) {
            $this->totalPages = $searchResult->totalPages;
            $this->previousSearchId = $searchResult->searchId;
            $this->page = $searchResult->pageIndex+1;
            return $this->parseRecords($searchResult->recordList->record);
        } 
        return null;
    }

    protected function init() : void
    {
        $this->setParameters();
        $this->initRequest();
    }

    protected function setParameters() 
    {
        $searchBooleanField = new SearchBooleanField();
        $searchBooleanField->searchValue = true;
        $searchDateField = new SearchDateField();
        $searchDateField->searchValue = (new \DateTime(self::FROM_DATE))->getTimestamp();
        $searchDateField->operator = SearchDateFieldOperator::onOrAfter;
        $this->search = new ItemSearchBasic;
        $this->search->isAvailable = $searchBooleanField;
        $this->search->created = $searchDateField;
        $this->service->setSearchPreferences(false, self::PER_PAGE);
    }

    public function initRequest()
    {
        $this->request = new SearchRequest();
        $this->request->searchRecord = $this->search;
    }

     public function setPaginatedRequest()
    {
        $this->request = new SearchMoreWithIdRequest();
        $this->request->searchId = $this->previousSearchId;
        $this->request->pageIndex = $this->page;
    }

    public function getTotalPages()
    {
        return $this->totalPages;
    }

    public function getCurrentPage()
    {
        return $this->page;
    }

    public function getLastPage()
    {
        return $this->page-1;
    }

    public function inLoop() 
    {
        if($this->totalPages != null) {
            return $this->page <= $this->totalPages;
        }
        return true;
    }

    private function getCustomFieldLabel($id) 
    {
        $customField = collect(self::CUSTOM_FIELD_MAP)->firstWhere('id', $id);
        return optional($customField)['label'];
    }

    public function parseRecords($records)
    {
        return NetsuiteProduct::collection($records);
    }
}
