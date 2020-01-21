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
use App\Models\SalesRep;
use App\Services\NetSuite\SalesRep\SavedSearch as SalesRepSavedSearch;

/**
 * Class deletePostsCommand
 *
 * @category Console_Command
 * @package  App\Console\Commands
 */
class SyncSalesReps extends Command
{
    private $savedSearch;
    private $bar;
    private $response;
    private $totalPages;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = "sync:sales-reps";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Sync sales rep data to local database";


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->initSavedSearch();
        $salesReps = $this->savedSearch->search();
        $this->updateAccounts($salesReps);
    }

    private function initSavedSearch()
    {
        $this->savedSearch = new SalesRepSavedSearch;
    }


    private function updateAccounts($response)
    {
        $response->map(function($account){
            try {
                $salesRep = SalesRep::firstOrNew(['nsid' => $account['nsid']]);
                $salesRep->fill($account);
                $salesRep->save();
            } catch (\Exception $e) {
                $this->error($e->getMessage());
            }
        });
    }
}
