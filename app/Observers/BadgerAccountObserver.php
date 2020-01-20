<?php

namespace App\Observers;

use App\Models\BadgerAccount;

class BadgerAccountObserver
{
    /**
     * Handle the BadgerAccount "creating" event.
     *
     * @param  \App\Models\BadgerAccount  $badgerAccount
     * @return void
     */
    public function creating(BadgerAccount $badgerAccount)
    {
        // dd($badgerAccount);
        $badgerAccount->_ChangeType = BadgerAccount::CHANGE_TYPE_INSERT;
    }

    /**
     * Handle the BadgerAccount "updating" event.
     *
     * @param  \App\Models\BadgerAccount  $badgerAccount
     * @return void
     */
    public function updating(BadgerAccount $badgerAccount)
    {
        // dd($badgerAccount);
        $badgerAccount->_ChangeType = BadgerAccount::CHANGE_TYPE_UPDATE;
    }
}
