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
    SavedSearch as NSSavedSearch,
    Record as NSCustomerRecord
};
use App\Services\Badger\Badger as BadgerService;

/**
 * Class deletePostsCommand
 *
 * @category Console_Command
 * @package  App\Console\Commands
 */
class SyncBadgerAccounts extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = "sync:badger";

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
        $NSCust = new NSSavedSearch('customsearch_badger_sync');

        $response = $NSCust->search();
        $totalPages = $NSCust->getTotalPages();

        $bar = $this->output->createProgressBar($totalPages);
        $bar->start();

        $this->info("Updating local data");
        $this->updateAccounts($response);
        $bar->advance();

        for($page=2;$page<=$totalPages;$page++) {
            $response = $NSCust->search($page);
            $this->updateAccounts($response);

            $bar->advance();
        }

        $bar->finish();

        $this->info("Pushing to Badger Maps");
        $this->pushToBadger();
    }

    public function updateAccounts($response)
    {
        $this->info("Retrieving data from NetSuite");
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
