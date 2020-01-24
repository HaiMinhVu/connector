<?php

namespace App\Jobs;

use App\Models\BadgerAccount;
use App\Services\Badger\Badger as BadgerService;

class PushToBadger extends Job
{

    private $fromDate;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($fromDate)
    {
        $this->fromDate = $fromDate;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $badgerService = new BadgerService;
            $badgerService->exportCustomers($this->fromDate);
        } catch(\Exception $e) {
            dd($e->getMessage());
        }
    }
}
