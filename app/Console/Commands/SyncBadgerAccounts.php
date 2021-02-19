<?php
namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use App\Models\{
    BadgerAccount
};
use App\Services\NetSuite\Customer\{
    SavedSearch as CustomerSavedSearch
};

use App\Services\Badger\Badger as BadgerService;
use Carbon\Carbon;
use Log;

class SyncBadgerAccounts extends Command
{
    const CUSTOMER_SAVED_SEARCH_ID = 'customsearch_badger_sync';
    const BADGER_ACCOUNT_QUEUE = 'netsuite';
    const BADGER_UPDATE_QUEUE = 'badger';

    private $savedSearch;
    private $bar;
    private $response;
    private $totalPages;
    private $fromDate;
    private $badgerService;

    protected $signature = "sync:badger {--from-date= : Specify the last modified date for results}";
    protected $description = "Sync savedsearch from NS to badger";

    public function __construct(BadgerService $badgerService, CustomerSavedSearch $savedSearch)
    {
        parent::__construct();
        $this->badgerService = $badgerService;
        $this->savedSearch = $savedSearch;
    }

    public function handle()
    {
        $this->setFromDate();
        $this->info("Retrieving data from NetSuite");
        $this->runInitialSearch();
        $this->initProgressBar();
        $this->updateAccounts($this->response);
        $this->runSearches();
        $this->pushToBadger($this->fromDate);
    }

    private function runInitialSearch()
    {
        try {
            $this->response = $this->savedSearch->search();
            $this->totalPages = $this->savedSearch->getTotalPages();
            Log::info($this->totalPages);
        } catch(\Exception $e) {
            Log::info($e);
            $this->info("Retrying initial search");
            $this->runInitialSearch();
        }
    }

    private function runSearches()
    {
        $this->bar->advance();
        for($page=2;$page<=$this->totalPages;$page++) {
            $this->bar->setProgress($page);
            $this->setResponse($page);
        }
        $this->bar->finish();
    }

    private function setResponse($page)
    {
        try {
            $this->response = $this->savedSearch->search($page);
            $this->updateAccounts($this->response);
        } catch(\Exception $e) {
            $this->info("Retrying page {$page}/{$this->savedSearch->getTotalPages()}");
            $this->setResponse($page);
        }
    }

    private function initProgressBar()
    {
        $this->bar = $this->output->createProgressBar($this->totalPages);
        $this->bar->start();
    }

    private function setFromDate()
    {
        $fromDate = ($this->option('from-date') === null) ? Carbon::now()->subDay() : $this->option('from-date');
        $this->fromDate = Carbon::parse($fromDate)->startOfDay();
        $this->savedSearch->setFromDate($fromDate);
    }

    private function updateAccounts($response)
    {
        $response->map(function($account){
            BadgerAccount::updateOrCreate(['nsid' => $account['nsid']], $account);
        });
    }

    private function pushToBadger($fromDate)
    {
        $this->badgerService->exportCustomers($fromDate);
    }

}
