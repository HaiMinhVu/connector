<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\NetSuite\Product\CustomList as ProductCategory;

class SyncProductCategories extends Command
{
    const NETSUITE_PRODUCT_CATEGORY = 106;

    private $productCategory;
    private $client;
    private $response;

    protected $signature = "sync:product-categories";
    protected $description = "Sync product categories";

    public function __construct(ProductCategory $productCategory)
    {
        parent::__construct();
        $this->productCategory = $productCategory;
    }

    public function handle()
    {
        $this->setHTTPClient();
        $this->response = $this->productCategory->getCustomList(self::NETSUITE_PRODUCT_CATEGORY);
        $this->pushToAPI($this->response);
    }

    private function setHTTPClient()
    {
        $this->client = new \GuzzleHttp\Client([
            'base_uri' => config('services.qa.endpoint'),
            'headers' => ['Authorization' => config('services.qa.token')]
        ]);
    }

    private function pushToAPI($response)
    {
        try{
            $res = $this->client->post('product-category/ns-update', ['json' => $response]);
            return $res;
        } catch(\Exception $e) {
            $this->info($e);
            dd($e);
        }            
    }

}
