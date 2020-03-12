<?php

namespace App\Jobs;

use GuzzleHttp\Client;


class PushNetsuiteProductsToAPI extends Job
{

    private $data;
    private $client;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
        $this->client = new Client([
        	'base_uri' => config('services.sellmark.endpoint'),
        	'headers' => ['X-Api-Key' => config('services.sellmark.token')]
        ]);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
        	$response = $this->client->post(['json' => $this->data]);
        } catch(\Exception $e) {
            print_r($e->getMessage());
        }
    }
}
