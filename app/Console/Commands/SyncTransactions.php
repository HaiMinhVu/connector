<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\NetSuite\ReturnAuthorization\{
    Record,
    SavedSearch as RASavedSearch
};
use Carbon\Carbon;


class SyncTransactions extends Command
{

    private $savedSearch;
    private $response;
    private $bar;
    private $totalPages;
    private $fromDate;
    private $client;

    protected $signature = "sync:transactions {--from-date= : Specify the last modified date for results}";


    protected $description = "Sync RA and CM from NetSuite";

    public function __construct(RASavedSearch $savedSearch)
    {
        parent::__construct();
        $this->savedSearch = $savedSearch;
    }

    public function handle()
    {
        $this->setFromDate();
        $this->setHTTPClient();
        $this->info("Retrieving data from NetSuite");
        $this->runInitialSearch();
        $this->initProgressBar();
        $this->runSearches();
        
    }

    private function setFromDate()
    {
        $fromDate = ($this->option('from-date') === null) ? Carbon::now()->subDay() : $this->option('from-date');
        $this->fromDate = Carbon::parse($fromDate)->startOfDay();
        $this->savedSearch->setFromDate($fromDate);
    }

    private function initProgressBar()
    {
        $this->bar = $this->output->createProgressBar($this->totalPages);
        $this->bar->start();
    }

    private function runInitialSearch()
    {
        try {
            $this->response = $this->savedSearch->search();
            $this->totalPages = $this->savedSearch->getTotalPages();
            $this->pushToQA($this->response);
        } catch(\Exception $e) {
            $this->info($e);
            dd($e);
            // $this->info("Retrying initial search");
            // $this->runInitialSearch();
        }
    }

    private function runSearches()
    {
        $this->bar->advance();
        for($page=2;$page<=$this->totalPages;$page++) {
            $this->bar->setProgress($page);
            $this->setResponse($page);
        }
        $this->bar->finish();
    }

    private function setResponse($page)
    {
        try {
            $this->response = $this->savedSearch->search($page);
            $this->pushToQA($this->response);
        } catch(\Exception $e) {
            $this->info("Retrying page {$page}/{$this->savedSearch->getTotalPages()}");
            dd($e);
            $this->setResponse($page);
        }
    }

    private function setHTTPClient()
    {
        $this->client = new \GuzzleHttp\Client([
            'base_uri' => config('services.qa.endpoint'),
            'headers' => ['Authorization' => config('services.qa.token')]
        ]);
    }

    private function pushToQA($response)
    {
        try{
            $this->client->post('ns-transaction/mass-update', ['json' => $response]);
        } catch(\Exception $e) {
            $this->info($e);
            dd($e);
        }
    }


}
