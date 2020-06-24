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
    private $response;

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

    public function __construct(SalesRepSavedSearch $savedSearch)
    {
        parent::__construct();
        $this->savedSearch = $savedSearch;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('Updating Sales Reps');
        $this->handleAction();
    }

    private function handleAction()
    {
        try {
            $this->response = $this->savedSearch->search();
            $this->updateAccounts();
            $this->info('Update complete');
        } catch(\Exception $e) {
            // $this->error($e->getMessage());
            $this->handleAction();
        }
    }

    private function updateAccounts()
    {
        $this->response->map(function($account){
            try {
                SalesRep::updateOrCreate(['nsid' => $account['nsid']], $account);
            } catch(\Exception $e) {
                // $this->error($e->getMessage());
            }
        });
    }
}
