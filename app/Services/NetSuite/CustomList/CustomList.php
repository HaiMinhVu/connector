<?php

namespace App\Services\NetSuite\CustomList;

use App\Services\NetSuite\Service;
use NetSuite\Classes\{
    GetRequest,
    RecordRef
};

class CustomList extends Service {

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

    public function getCustomList($nsid)
    {
        $this->request->baseRef->internalId = $nsid;
        $this->request->baseRef->type = self::TYPE;
        return $this->arrayFormat($this->service->get($this->request));
    }

    public function arrayFormat($response)
    {
        $res = [];
        $items = $response->readResponse->record->customValueList->customValue;
        foreach ($items as $item) {
            $res[$item->valueId] = $item->value;
        }
        return $res;
    }
}
