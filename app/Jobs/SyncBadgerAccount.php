<?php

namespace App\Jobs;

use App\Models\BadgerAccount;
use App\Services\NetSuite\Customer\SavedSearch as CustomerSavedSearch;

class SyncBadgerAccount extends Job
{

    private $data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $records = CustomerSavedSearch::getRecords($this->data);
        $data = array_merge(['_Address' => $this->data['_Address'], 'Business Email' => $this->data['Business Email']], $records);
        $badgerAccount = BadgerAccount::updateOrCreate(
            ['nsid' => $this->data['nsid']],
            $data
        );
        $badgerAccount->fill($records);
        $badgerAccount->save();
    }
}
