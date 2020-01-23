<?php
/**
 *
 * PHP version >= 7.0
 *
 * @category Console_Command
 * @package  App\Console\Commands
 */

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
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

/**
 * Class deletePostsCommand
 *
 * @category Console_Command
 * @package  App\Console\Commands
 */
class SyncBadgerAccounts extends Command
{
    const CUSTOMER_SAVED_SEARCH_ID = 'customsearch_badger_sync';

    private $savedSearch;
    private $bar;
    private $response;
    private $totalPages;
    private $fromDate;
    private $badgerService;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = "sync:badger {--from-date= : Specify the last modified date for results}";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Sync badger data";

    public function __construct(BadgerService $badgerService, CustomerSavedSearch $savedSearch)
    {
        parent::__construct();
        $this->badgerService = $badgerService;
        $this->savedSearch = $savedSearch;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info("Retrieving data from NetSuite");
        $this->setFromDate();

        $this->runInitialSearch();
        $this->info("Updating local data");
        $this->initProgressBar();
        $this->updateAccounts($this->response);

        $this->runSearches();

        $this->info("Pushing to Badger Maps");
        $this->pushToBadger();
    }

    private function runInitialSearch()
    {
        $this->response = $this->savedSearch->search();
        $this->totalPages = $this->savedSearch->getTotalPages();
    }

    private function runSearches()
    {
        $this->bar->advance();
        for($page=184;$page<=$this->totalPages;$page++) {
            $this->bar->setProgress($page);
            try {
                $this->response = $this->savedSearch->search($page);
            } catch(\Exception $e) {
                $this->error(PHP_EOL.$e->getMessage());
                $this->info("Retrying page {$page}/{$this->savedSearch->getTotalPages()}");
                $this->response = $this->savedSearch->search($page);
            }
            $this->updateAccounts($this->response);
        }
        $this->bar->finish();
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
            $badgerAccount = BadgerAccount::updateOrCreate(['nsid' => $account['nsid']], $account);
        });
    }

    private function pushToBadger()
    {
        try {
            $this->badgerService->exportCustomers($this->fromDate);
        } catch(\Exception $e) {
            $this->error(PHP_EOL.$e->getMessage());
        }
    }
}
