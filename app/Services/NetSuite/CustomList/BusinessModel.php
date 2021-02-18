<?php

namespace App\Services\NetSuite\CustomList;

use App\Services\NetSuite\Service;
use NetSuite\Classes\{
    GetRequest,
    RecordRef
};

class BusinessModel extends Service {

    const BUSINESS_MODEL_ID = '124';
    const TYPE = 'customList';

    private $response;

    public function __construct()
    {
        parent::__construct();
        $this->init();
    }

    protected function init()
    {
        $request = new GetRequest();
        $request->baseRef = new RecordRef();
        $request->baseRef->internalId = self::BUSINESS_MODEL_ID;
        $request->baseRef->type = self::TYPE;
        $this->response = $this->service->get($request);
    }

    public function getRequest()
    {
        $res = [];
        $items = $this->response->readResponse->record->customValueList->customValue;
        foreach ($items as $item) {
            $res[$item->valueId] = $item->value;
        }
        return $res;
    }
}
