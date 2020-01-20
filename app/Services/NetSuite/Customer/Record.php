<?php

namespace App\Services\NetSuite\Customer;

use Illuminate\Support\Facades\Cache;
use App\Services\NetSuite\Service;
use NetSuite\Classes\{
    GetRequest,
    RecordRef,
    RecordType
};

class Record extends Service {

    private $request;

    public function __construct()
    {
        parent::__construct();
        $this->setRequest();
    }

    private function setRequest()
    {
        $this->request = new GetRequest();
        $this->request->baseRef = new RecordRef();
    }

    public function getByNSID($customerNSID)
    {
        $this->request->baseRef->internalId = $customerNSID;
		$this->request->baseRef->type = RecordType::customer;

        return Cache::remember("customer_record_{$customerNSID}", self::CACHE_SECONDS, function(){
            return $this->service->get($this->request);
        });
    }

}
