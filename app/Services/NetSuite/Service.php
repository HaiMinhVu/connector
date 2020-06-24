<?php

namespace App\Services\NetSuite;

use NetSuite\NetSuiteService;

class Service {

    const CACHE_SECONDS = 21600;

    protected $service;

    public function __construct()
    {
        $this->setService();
    }

    private function setService()
    {
        $this->service = new NetSuiteService([
            "endpoint" => config('services.netsuite.endpoint'),
            "host" => config('services.netsuite.host'),
            "email" => config('services.netsuite.email'),
            "password" => config('services.netsuite.password'),
            "role" => config('services.netsuite.role'),
            "account" => config('services.netsuite.account'),
            "app_id" => config('services.netsuite.app_id')
        ]);
	    $this->service->addHeader('keep_alive', false);
    }
}
