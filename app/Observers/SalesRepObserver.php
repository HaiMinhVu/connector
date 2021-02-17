<?php

namespace App\Observers;

use App\Models\SalesRep;

class SalesRepObserver
{
    /**
     * Handle the SalesRep "creating" event.
     *
     * @param  \App\Models\SalesRep  $salesRep
     * @return void
     */
    public function creating(SalesRep $salesRep)
    {
        // $salesRep->ismod = 0;
    }

    /**
     * Handle the SalesRep "updating" event.
     *
     * @param  \App\Models\SalesRep  $salesRep
     * @return void
     */
    public function updating(SalesRep $salesRep)
    {
        // $salesRep->ismod = 1;
    }
}
