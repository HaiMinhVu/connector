<?php

namespace App\Jobs;

use App\Models\{
    BadgerAccount,
    CustEntity,
    SalesRep
};
use App\Services\NetSuite\Customer\{
    SavedSearch as CustomerSavedSearch,
    Record as NSCustomerRecord
};
use App\Services\Badger\Badger as BadgerService;
use Carbon\Carbon;
use App\Jobs\{
    PushToBadger,
    SyncBadgerAccount
};

class GetBadgerData extends Job
{

    const CUSTOMER_SAVED_SEARCH_ID = 'customsearch_badger_sync';
    const BADGER_INITIAL_QUEUE = 'badger_initial_queue';
    const BADGER_ACCOUNT_QUEUE = 'netsuite';
    const BADGER_UPDATE_QUEUE = 'badger';

    private $fromDate;
    private $page;
    private $response;
    private $savedSearch;
    private $totalPages;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($fromDate = null, $page = 1)
    {
        $this->fromDate = $fromDate;
        $this->page = $page;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->savedSearch = new CustomerSavedSearch;
        $this->setFromDate();
        $this->runSearch();
        $this->addAllToQueue();
    }

    private function runSearch()
    {
        $this->response = $this->savedSearch->search($this->page);
        $this->totalPages = $this->savedSearch->getTotalPages();
    }

    private function addAllToQueue()
    {
        dispatch(new self($this->fromDate, $this->page++))->onQueue(self::BADGER_INITIAL_QUEUE);

        $this->response->map(function($item){
            dispatch(new SyncBadgerAccount($item))->onQueue(self::BADGER_ACCOUNT_QUEUE);
        });
    }

    private function setFromDate()
    {
        $fromDate = ($this->fromDate) ? Carbon::now()->subDay() : $this->fromDate;
        $this->fromDate = Carbon::parse($fromDate)->startOfDay();
        $this->savedSearch->setFromDate($fromDate);
    }

}
