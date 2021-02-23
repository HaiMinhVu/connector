<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Badger\Badger as BadgerService;
use App\Models\{
    Checkin,    
};
use Log;

class SyncBadgerCheckins extends Command
{

    protected $signature = 'sync:checkins';
    protected $description = 'Sync check-ins from badger to NS';

    private $badgerService;
    private $checkins;

    public function __construct(BadgerService $badgerService)
    {
        parent::__construct();
        $this->badgerService = $badgerService;
    }

    public function handle()
    {
        $this->badgerService->downloadCheckins();
        $this->badgerService->insertCheckins();
        // $this->badgerService->insertCheckins();
    }
    
}