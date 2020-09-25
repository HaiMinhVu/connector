<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Storage;
use ZipArchive;
use App\Models\{
    Checkin,    
};

class InsertCheckins extends Command
{
    
    protected $signature = 'insert:checkins';
    protected $description = 'insert check-ins from local to database';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->addCheckin();
    }    

    public function addCheckin(){
        $filenames = $this->getAllFilenames();
        foreach ($filenames as $filename) {
            $file = Storage::disk('local')->get($filename);
            echo "Processing File ".$filename.PHP_EOL;
            $this->processFile($filename);
        }
    }

    public function getAllFilenames(){
        return array_filter(Storage::disk('local')->files(), function ($item) {
            return strpos($item, '.csv');
        });
    }

    public function processFile($filename){
        $file = fopen('storage/app/'.$filename, 'r');
        $processedAll = 1;
        $firstline = TRUE;
        while (($row = fgetcsv($file)) !== FALSE) {
            if($firstline){
                $firstline = FALSE;
                continue;
            }
            if($this->createCheckin($row) == false){
                $processedAll = 0;
            }
        }
        if($processedAll){
            $this->saveProcessedFile($filename);
        }
    }

    public function saveProcessedFile($filename){
        $zipFileName = 'savedCheckins.zip';
        $zip = new ZipArchive;
        if ($zip->open('storage/app/public/'.$zipFileName, ZipArchive::CREATE) === TRUE) {
            if($zip->addFile('storage/app/'.$filename, $filename)){
                $zip->close();
                $this->deleteFile($filename);
            }            
        }

    }

    public function deleteFile($filename){
        Storage::disk('local')->delete($filename);
    }

    public function createCheckin($data) : bool{
        $checkin = new Checkin;
        $checkin->rep_email = $data[1];
        $checkin->account_name = $data[2];
        $checkin->account_address = $data[3];
        $checkin->date = $data[4];
        $checkin->time = $data[5];
        $checkin->local_time = $data[6];
        $checkin->timezone = $data[7];
        $checkin->type = $data[8];
        $checkin->customer_id = $data[10];
        $checkin->estimate_number = $data[11];
        $checkin->estimate_total = $data[12];
        $checkin->po_number = $data[13];
        $checkin->po_total = $data[14];
        $checkin->note = json_encode($data[15]);
        if(!$checkin->save()){
            return false;
        }
    }


}
