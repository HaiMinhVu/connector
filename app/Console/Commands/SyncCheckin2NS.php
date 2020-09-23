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

    protected $signature = 'sync:checkins';
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
    	return Checkin::where('is_processed', 0)->get();
    }    

    public function processCheckin($data){
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
    	else{
    		$this->processMeeting($data);
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

    public function processEmail($data){

    }

    public function processPhoneCall($data){
    	echo "phone call";
    }

    public function processLetter($data){
    	
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
		    echo "Success";
		    $this->updateCheckin($this->id);
		} else {
		    echo "Error";
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

