<?php

namespace App\Services\NetSuite\Product;

use App\Services\NetSuite\Service;
use NetSuite\Classes\{
    GetRequest,
    RecordRef,
    RecordType
};

class CustomList extends Service {

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
        $this->request->baseRef->type = RecordType::customList;
        $response = $this->service->get($this->request);
        $results = collect($response->readResponse->record->customValueList->customValue);
        return $this->filterResponse($results)->map(function($result){
            return $this->parseResponse($result);
        });
    }

    private function filterResponse($results)
    {
        return $results->filter(function($item){
            return $item->isInactive == false;
        });
    }

    private function parseResponse($result)
    {
        return [
            'nsid' => $result->valueId,
            'name' => $result->value
        ];
    }
}
