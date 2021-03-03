<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Badger\Badger as BadgerService;
use App\Services\NetSuite\Badger\Checkin as BadgerCheckin;

class SyncBadgerCheckins extends Command
{

    protected $signature = 'sync:checkins';
    protected $description = 'Sync check-ins from badger to NS';

    private $badgerService;
    private $badgerCheckin;

    public function __construct(BadgerService $badgerService, BadgerCheckin $badgerCheckin)
    {
        parent::__construct();
        $this->badgerService = $badgerService;
        $this->badgerCheckin = $badgerCheckin;
    }

    public function handle()
    {
        $this->info('Downloading check-ins from badger');
        $this->downloadCheckins();
        $this->info('Inserting check-ins to database');
        $this->insertCheckins();
        $this->info('Pushing check-ins to NetSuite');
        $this->syncCheckins();
    }

    private function downloadCheckins()
    {
        try {
            $this->badgerService->downloadCheckins();
        } catch(\Exception $e) {
            $this->info("Retrying Downloading check-ins from badger");
            $this->downloadCheckins();
        }
    }

    private function insertCheckins()
    {
        try {
            $this->badgerService->insertCheckins();
            return true;
        } catch(\Exception $e) {
            $this->info("Retrying Inserting check-ins to database");
            $this->insertCheckins();
        }
    }

    private function syncCheckins()
    {
        try {
            $this->badgerCheckin->syncCheckins();
        } catch(\Exception $e) {
            $this->info("Retrying Pushing check-ins to NetSuite");
            $this->syncCheckins();
        }
    }
    
}