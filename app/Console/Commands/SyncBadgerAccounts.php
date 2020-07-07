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
use Carbon\Carbon;
use App\Jobs\GetBadgerData;
use Queue;

/**
 * Class SyncBadgerAccounts
 *
 * @category Console_Command
 * @package  App\Console\Commands
 */
class SyncBadgerAccounts extends Command
{
    const CUSTOMER_SAVED_SEARCH_ID = 'customsearch_badger_sync';
    const BADGER_ACCOUNT_QUEUE = 'netsuite';
    const BADGER_UPDATE_QUEUE = 'badger';

    private $fromDate;

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
        $this->setFromDate();
        dispatch(new GetBadgerData($this->fromDate))->onQueue(GetBadgerData::BADGER_INITIAL_QUEUE);
    }

    private function setFromDate()
    {
        $fromDate = ($this->option('from-date') === null) ? Carbon::now()->subDay() : $this->option('from-date');
        $this->fromDate = Carbon::parse($fromDate)->startOfDay();
        $this->savedSearch->setFromDate($fromDate);
    }
}
