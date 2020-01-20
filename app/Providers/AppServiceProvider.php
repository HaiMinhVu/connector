<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\{BadgerAccount, SalesRep};
use App\Observers\{BadgerAccountObserver, ModelObserver, SalesRepObserver};

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        BadgerAccount::observe(BadgerAccountObserver::class);
        BadgerAccount::observe(ModelObserver::class);
        SalesRep::observe(ModelObserver::class);
        SalesRep::observe(SalesRepObserver::class);
    }
}
