<?php

namespace App\Services\NetSuite\CustomList;

use App\Services\NetSuite\Service;
use NetSuite\Classes\{
    GetRequest,
    RecordRef,
    RecordType
};

class AccountCategory extends Service {

    const ACCOUNT_CATEGORY_LIST_ID = '116';
    const TYPE = 'customList';

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

    public function getByNSID($employeeNSID)
    {
        $request->baseRef->internalId = $employeeNSID;
        $request->baseRef->type = RecordType::employee;
        return $this->service->get($this->request);
    }
}
