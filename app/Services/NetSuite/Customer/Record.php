<?php

namespace App\Services\NetSuite\Customer;

use Illuminate\Support\Facades\Cache;
use App\Services\NetSuite\Service;
use NetSuite\Classes\{
    AddRequest,
    GetRequest,
    RecordRef,
    RecordType,

};

class Record extends Service {

    private $getRequest;
    private $addRequest;

    public function __construct()
    {
        parent::__construct();
        $this->setGetRequest();
        $this->setAddRequest();
    }

    private function setGetRequest()
    {
        $this->getRequest = new GetRequest();
        $this->getRequest->baseRef = new RecordRef();
    }

    private function setAddRequest()
    {
        $this->addRequest = new AddRequest();
        $this->addRequest->baseRef = new RecordRef();
    }

    public function getByNSID($customerNSID)
    {
        $this->getRequest->baseRef->internalId = $customerNSID;
		$this->getRequest->baseRef->type = RecordType::customer;
        return $this->service->get($this->getRequest);
    }

    public function create($data)
    {
        $customer = $this->parseData($data);
        $this->addRequest->record = $customer;
        $res = $this->service->add($this->addRequest);
        if($res->writeResponse->status->isSuccess){
            return $res->writeResponse->baseRef->internalId;
        }
        else{
            dd('error');
        }
    }

    private function parseData($data){
        $salesRep = new RecordRef();
        $salesRep->internalId = $data->rep_id;
        $salesRep->type = RecordType::employee;
        $customer = new Customer();
        $customer->companyName = $data->account_name;
        $customer->salesRep = $salesRep;
        return $customer;
    }
}
