<?php

namespace App\Services\NetSuite\CustomList;

use App\Services\NetSuite\Service;
use NetSuite\Classes\{
    GetRequest,
    RecordRef,
    RecordType
};

class CustomerStatus extends Service {

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

    public function getStatus($nsid)
    {
        $this->request->baseRef->internalId = $nsid;
        $this->request->baseRef->type = RecordType::customerStatus;
        return $this->formatResult($this->service->get($this->request));
    }

    public function formatResult($response)
    {
        return $response->readResponse->record->stage.' '.$response->readResponse->record->name;
    }
}
