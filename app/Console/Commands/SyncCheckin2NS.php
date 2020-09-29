<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

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
use Illuminate\Support\Facades\DB;

class SyncCheckin2NS extends Command
{
	protected $service;
 	protected $pendingCheckins;
 	private $id;

    protected $signature = 'checkins:sync';
    protected $description = 'push check-ins to netsuite';

    public function __construct()
    {
        parent::__construct();
        $this->init();
        $this->setService();
    }

    public function handle()
    {
        foreach ($this->pendingCheckins as $checkin) {
        	$this->processCheckin($checkin);
        	echo PHP_EOL;
        }
    }    

    public function init(){
    	$this->pendingCheckins = $this->readCheckin();
    }

    private function setService()
    {
        $this->service = new NetSuiteService([
            "endpoint" => config('services.netsuite.endpoint'),
            "host" => config('services.netsuite.host'),
            "email" => config('services.netsuite.email'),
            "password" => config('services.netsuite.password'),
            "role" => config('services.netsuite.role'),
            "account" => config('services.netsuite.account'),
            "app_id" => config('services.netsuite.app_id')
        ]);
    }

    public function readCheckin(){
    	return Checkin::where('is_processed', 0)->orderBy('id')->get();
    }    

    public function processCheckin($data){
        echo "Processing ".$data->id;
    	$this->id = $data->id;
    	$data->rep_id = $this->getRepID($data->rep_email);
    	if($data->type == 'PhoneCall'){
    		$this->processPhoneCall($data);
    	}
    	else if($data->type == 'Email'){
    		$this->processEmail($data);
    	}
    	else if($data->type == 'Letter'){
    		$this->processLetter($data);
    	}
    	else if($data->type == 'Meeting'){
    		$this->processMeeting($data);
    	}
    	else{
    		echo "Missing Type";
    		return;
    	}
    }

    public function getRepID($rep_email){
    	$salerep = SalesRep::where('email', $rep_email)->first();
    	if($salerep){
    		return $salerep->nsid;
    	}
    	else{
    		return 300599; // default to hvu@sellmark.net employee id;
    	}
    }

    public function processMeeting($data){
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
		$meeting->message = $data->note.PHP_EOL.'Estimate #: '.$data->estimate_number. PHP_EOL.'Estimate Total: '.$data->estimate_total.PHP_EOL.'PO #: '.$data->po_number.PHP_EOL.'PO Total: '.$data->po_total;
		$meeting->status = CalendarEventStatus::_tentative; //_cancelled, _completed, _confirmed or _tentative
		$meeting->accessLevel = CalendarEventAccessLevel::_public; //_public, _private or _showAsBusy
		$meeting->starttime = $meeting->endtime = $data->time;
		$meeting->startDate = date_format(date_create($data->date),DATE_ATOM);
		$meeting->allDayEvent = false;
		$meeting->sendEmail = false;
        $meeting->customform = -110;
    	$this->pushCheckin($this->addListRequest($meeting));
    }

    public function processPhoneCall($data){
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
		$this->pushCheckin($this->addListRequest($phonecall));
    }

    public function processEmail($data){
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
    	$this->pushCheckin($this->addListRequest($email));
    }

    public function processLetter($data){
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
    	$this->pushCheckin($this->addListRequest($letter));
    }

    public function createRecordRef($internalId){
    	$recordRef = new RecordRef();
    	$recordRef->internalId = $internalId;
    	return $recordRef;
    }

    public function addListRequest($data){
    	$request = new AddListRequest();
    	$request->record[] = $data;
    	return $request;
    }

    public function pushCheckin($request){
    	$addResponse = $this->service->addList($request);
		if ($addResponse->writeResponseList->status->isSuccess ==  true) {
			echo " Success.";
		    $this->updateCheckin($this->id);
		} else {
		    echo "Error ".$addResponse->writeResponseList->status->statusDetail;
		}
    }

    public function updateCheckin($id){
    	try{
    		Checkin::where('id', $id)->update(['is_processed' => 1]);
    	}catch(\Exception $e){
            return "Failed to Add.";
        }
    }
}

