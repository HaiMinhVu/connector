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
    const CUSTOMER_SAVED_SEARCH_ID = 'NETSUITE_SAVED_SEARCH_ID';

    private $savedSearch;
    private $bar;
    private $response;
    private $totalPages;

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


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info("Retrieving data from NetSuite");
        $this->initSavedSearch();
        $this->setFromDate();

        $this->runInitialSearch();
        $this->info("Updating local data");
        $this->initProgressBar();
        $this->updateAccounts($this->response);

        $this->runSearches();

        $this->info("Pushing to Badger Maps");
        $this->pushToBadger();
    }

    private function initSavedSearch()
    {
        $this->savedSearch = new CustomerSavedSearch(self::NETSUITE_SAVED_SEARCH_ID);
    }

    private function runInitialSearch()
    {
        $this->response = $this->savedSearch->search();
        $this->totalPages = $this->savedSearch->getTotalPages();
    }

    private function runSearches()
    {
        $this->bar->advance();
        for($page=2;$page<=$this->totalPages;$page++) {
            try {
                $this->response = $this->savedSearch->search($page);
            } catch(\Exception $e) {
                $this->error(PHP_EOL.$e->getMessage());
                $this->info("Retrying page {$page}/{$this->savedSearch}");
                $this->response = $this->savedSearch->search($page);
            }
            $this->updateAccounts($this->response);
            $this->bar->advance();
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
        $this->savedSearch->setFromDate($fromDate);
    }

    private function updateAccounts($response)
    {
        $response->map(function($account){
            $badgerAccount = BadgerAccount::firstOrNew(['nsid' => $account['nsid']]);
            $badgerAccount->fill($account);
            $badgerAccount->save();
        });
    }

    private function pushToBadger()
    {
        $data = BadgerAccount::limit(100)->get();
        $badger = new BadgerService;
        $data = $data->map(function($account){
            $account = $account->attributesToArray();
            $account['src'] = null;
            return $account;
        });
        $badger->export($data->toArray());
    }
}
