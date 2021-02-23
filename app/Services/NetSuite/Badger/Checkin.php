<?php

namespace App\Services\NetSuite\Badger;

use App\Services\NetSuite\Service;
use NetSuite\NetSuiteService;
use NetSuite\Classes\{
    CalendarEvent,
    CalendarEventStatus,
    CalendarEventAccessLevel,
    RecordRef,
    AddListRequest,
    Message,
    PhoneCall,
    PhoneCallStatus
};
use App\Models\{
    SalesRep,
    Checkin,
};

class Checkin extends Service {

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
		$this->request->baseRef->type = RecordType::contact;
        return $this->service->get($this->request);
    }

}
