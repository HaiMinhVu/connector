<?php

namespace App\Services\NetSuite\Badger;

use App\Services\NetSuite\Service;
use NetSuite\NetSuiteService;
use NetSuite\Classes\{
    RecordRef,
    AddListRequest,
    CalendarEvent,
    CalendarEventStatus,
    CalendarEventAccessLevel,
    Message,
    PhoneCall,
    PhoneCallStatus
};
use App\Models\{
    SalesRep,
    BadgerCheckin,
    BadgerAccount
};

use App\Services\NetSuite\Customer\{
    Record as CustomerRecord
};


class Checkin extends Service {

    private $request;
    private $checkins;
    public function __construct()
    {
        parent::__construct();
        $this->initCheckins();
    }

    private function initCheckins(){
        $this->checkins = BadgerCheckin::where('is_processed', 0)->orderBy('id')->get();
    }

    public function syncCheckins()
    {
        foreach ($this->checkins as $checkin) {
            $this->processCheckin($checkin);
        }
    }



    private function processCheckin($data){
        echo "Processing ".$data->id;
        $data->rep_id = $this->getRepID($data->rep_email);
        if(!$this->checkExistingByNSID($data->customer_id)){
            if(!$this->checkExistingByName($data->account_name)){
                try{
                    $data->customer_id = $this->createNewCustomerOnNetsuite($data);
                    $this->createBadgerAccount($data);
                } catch(\Exception $e) {
                    dd($e);
                }
            }
            else{
                $data->customer_id = $this->getBadgerAccountByName($data->account_name);
            }
        }
        if($data->type == 'Phone Call'){
            $this->processPhoneCall($data);
        }
        else if($data->type == 'Email'){
            $this->processEmail($data);
        }
        else if($data->type == 'Letter'){
            $this->processLetter($data);
        }
        else{
            $this->processMeeting($data);
        }
    }

    private function checkExistingByNSID($nsid) {
        return BadgerAccount::where('nsid', $nsid)->exists();
    }

    private function checkExistingByName($name) {
        return BadgerAccount::where('company_name', $name)->exists();
    }

    private function createNewCustomerOnNetsuite($data){
        $customer = new CustomerRecord();
        return $customer->create($data);
    }

    private function getBadgerAccountByName($name){
        $account = BadgerAccount::where('company_name', $name)->first();
        return $account->nsid;
    }

    private function createBadgerAccount($data){
        $account = $this->parseBadgerAccountData($data);
        BadgerAccount::updateOrCreate(['nsid' => $account['nsid']], $account);
    }

    private function parseBadgerAccountData($data){
        return [
            'nsid' => $data->customer_id,
            'company_name' => $data->account_name,
            'sale_rep' => $data->rep_email
        ];
    }

    private function getRepID($rep_email){
        $salerep = SalesRep::where('email', $rep_email)->first();
        if($salerep){
            return $salerep->nsid;
        }
        else{
            return -5; // default to Greg;
        }
    }

    private function processMeeting($data){
        $meeting = new CalendarEvent();
        $meeting->company = $this->createRecordRef($data->customer_id);
        $meeting = new CalendarEvent();
        $meeting->company = $this->createRecordRef($data->customer_id);
        $meeting->organizer = $this->createRecordRef($data->rep_id);
        $meeting->attendeeList = array(
            $this->createRecordRef($data->rep_id),
            $this->createRecordRef($data->customer_id)
        );
        $meeting->owner = $this->createRecordRef($data->rep_id);
        $meeting->location = $data->account_address;
        $meeting->title = "[Badger] Meet with ".$data->account_name;
        $meeting->message = 'Note: '.$data->note.PHP_EOL.'Decision Maker: '.$data->decision_maker_name. PHP_EOL.'Next Step: '.$data->next_step;
        $meeting->status = CalendarEventStatus::_tentative; //_cancelled, _completed, _confirmed or _tentative
        $meeting->accessLevel = CalendarEventAccessLevel::_public; //_public, _private or _showAsBusy
        $meeting->starttime = $meeting->endtime = $data->time;
        $meeting->startDate = date_format(date_create($data->date),DATE_ATOM);
        $meeting->allDayEvent = false;
        $meeting->sendEmail = false;
        $meeting->customform = -110;
        $this->pushCheckin($this->addListRequest($meeting), $data->id);
    }

    private function processPhoneCall($data){
        $phonecall = new PhoneCall();
        $phonecall->company = $this->createRecordRef($data->customer_id);
        $phonecall->owner = $this->createRecordRef($data->rep_id);
        $phonecall->assigned = $this->createRecordRef($data->rep_id);
        $phonecall->title="[Badger] Call ".$data->account_name;
        $phonecall->message = $data->note.PHP_EOL.'Estimate #: '.$data->estimate_number. PHP_EOL.'Estimate Total: '.$data->estimate_total.PHP_EOL.'PO #: '.$data->po_number.PHP_EOL.'PO Total: '.$data->po_total;
        $phonecall->status = PhoneCallStatus::_completed;
        $phonecall->sendEmail = false;
        $phonecall->starttime = $phonecall->endtime = $data->time;
        $phonecall->startDate = date_format(date_create($data->date),DATE_ATOM);
        $phonecall->messageDate = date_format(date_create($data->date),DATE_ATOM);
        $this->pushCheckin($this->addListRequest($phonecall), $data->id);
    }

    private function processEmail($data){
        $email = new Message();
        $email->company = $this->createRecordRef($data->customer_id);
        $email->author = $this->createRecordRef($data->customer_id);
        $email->recipient = $this->createRecordRef($data->rep_id);
        $email->subject = "[Badger] Email From ".$data->account_name;
        $email->message = $data->note.PHP_EOL.'Estimate #: '.$data->estimate_number. PHP_EOL.'Estimate Total: '.$data->estimate_total.PHP_EOL.'PO #: '.$data->po_number.PHP_EOL.'PO Total: '.$data->po_total;
        $email->emailed = true;
        $email->messageDateSpecified = true; 
        $email->allDayEvent = false;
        $email->starttime = $email->endtime = $data->time;
        $email->startDate = date_format(date_create($data->date),DATE_ATOM);
        $email->messageDate = date_format(date_create($data->date),DATE_ATOM);
        $this->pushCheckin($this->addListRequest($email), $data->id);
    }

    private function processLetter($data){
        $letter = new Message();
        $letter->company = $this->createRecordRef($data->customer_id);
        $letter->author = $this->createRecordRef($data->customer_id);
        $letter->recipient = $this->createRecordRef($data->rep_id);
        $letter->subject = "[Badger] Letter From ".$data->account_name;
        $letter->message = $data->note.PHP_EOL.'Estimate #: '.$data->estimate_number. PHP_EOL.'Estimate Total: '.$data->estimate_total.PHP_EOL.'PO #: '.$data->po_number.PHP_EOL.'PO Total: '.$data->po_total;
        $letter->emailed = true;
        $letter->messageDateSpecified = true; 
        $letter->allDayEvent = false;
        $letter->starttime = $letter->endtime = $data->time;
        $letter->startDate = date_format(date_create($data->date),DATE_ATOM);
        $letter->messageDate = date_format(date_create($data->date),DATE_ATOM);
        $this->pushCheckin($this->addListRequest($letter), $data->id);
    }

    private function createRecordRef($internalId){
        $recordRef = new RecordRef();
        $recordRef->internalId = $internalId;
        return $recordRef;
    }

    private function addListRequest($data){
        $request = new AddListRequest();
        $request->record[] = $data;
        return $request;
    }

    private function pushCheckin($request, $id){
        $addResponse = $this->service->addList($request);
        if ($addResponse->writeResponseList->status->isSuccess ==  true && $addResponse->writeResponseList->writeResponse[0]->status->isSuccess == true) {
            echo " Success.".PHP_EOL;
            $this->updateCheckin($id);
        } else {
            dd('Error Creating NS Checkin '.$addResponse);
        }
    }

    private function updateCheckin($id){
        try{
            BadgerCheckin::where('id', $id)->update(['is_processed' => 1]);
        }catch(\Exception $e){
            return "Failed to Update.";
        }
    }

}
