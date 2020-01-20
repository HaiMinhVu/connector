<?php

namespace App\Services\NetSuite;

use NetSuite\NetSuiteService;

class Service {

    const CACHE_SECONDS = 1800;
    const PER_PAGE = 10;

    protected $service;

    public function __construct()
    {
        $this->setService();
        $this->service->setSearchPreferences(false, self::PER_PAGE);
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
    }
}
