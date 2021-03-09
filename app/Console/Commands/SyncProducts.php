<?php
/**
 *
 * PHP version >= 7.0
 *
 * @category Console_Command
 * @package  App\Console\Commands
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\NetSuite\Inventory\Search as InventorySearch;
use Illuminate\Support\Facades\Storage;
use App\Jobs\PushNetsuiteProductsToAPI;
use Queue;

/**
 * Class deletePostsCommand
 *
 * @category Console_Command
 * @package  App\Console\Commands
 */
class SyncProducts extends Command
{
    const NETSUITE_PRODUCTS_QUEUE = 'netsuite_products';

    protected $inventorySearch;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = "sync:products {--from-date= : Specify the last modified date for results}";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Sync product data from NetSuite";

    public function __construct(InventorySearch $inventorySearch)
    {
        parent::__construct();
        $this->inventorySearch = $inventorySearch;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */

    public function handle()
    {
        $client = new \GuzzleHttp\Client([
            'base_uri' => config('services.sellmark.endpoint'),
            'headers' => ['X-Api-Key' => config('services.sellmark.token')]
        ]);

        $this->setFromDate();

        $this->inventorySearch->search(function($records) use ($client) {
            try {
                // $res = $client->post('products/netsuite', ['json' => $records]);
                $currentPage = $this->inventorySearch->getLastPage();
                $this->info("Page: {$currentPage}/{$this->inventorySearch->getTotalPages()}");
            } catch(\Exception $e) {
                $this->error($e->getMessage());
            }
        });
    }

    private function setFromDate()
    {
        if($this->option('from-date') !== null) {
            $this->inventorySearch->setFromDate($this->option('from-date'));
        }
    }

}
