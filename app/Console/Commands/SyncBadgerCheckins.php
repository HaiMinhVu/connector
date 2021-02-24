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
        $this->badgerService->downloadCheckins();
        $this->badgerService->insertCheckins();
        $this->badgerCheckin->syncCheckins();
    }
    
}