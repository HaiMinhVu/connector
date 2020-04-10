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
        // dd(['base_uri' => config('services.sellmark.endpoint'),
        // 	'headers' => ['X-Api-Key' => config('services.sellmark.token')]]);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
        	// print_r($this->data);
        	// dd('base_uri' => config('services.sellmark.endpoint'),
        	// 'headers' => ['X-Api-Key' => config('services.sellmark.token')]);
        	// $this->setupClient();
        	// dd($this->client);
        	// $response = $this->client->post('products/netsuite', ['json' => $this->data]);
        	// $client = new \GuzzleHttp\Client([
	        //     'base_uri' => config('services.sellmark.endpoint'),
	        //     'headers' => ['X-Api-Key' => config('services.sellmark.token')]
	        // ]);
	        // $res = $client->post('products/netsuite', ['json' => $this->data]);
        } catch(\Exception $e) {
            print_r($e->getMessage());
        }
    }

    private function setupClient()
    {
    	$this->client = new Client([
        	'base_uri' => config('services.sellmark.endpoint'),
        	'headers' => ['X-Api-Key' => config('services.sellmark.token')]
        ]);
    }
}
