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

            if($this->uploadFile2S3($filename) == 1){
                $this->processFile($filename);
            }
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
            if($this->createCheckin($row) == 0){
                $processedAll = 0;
            }
        }
        if($processedAll){
            $this->deleteFile($filename);
        }
    }

    public function uploadFile2S3($filename){   
        $file = Storage::disk('local')->get($filename);
        $newfilename = date("mdY").'_'.$filename;
        if(Storage::disk('s3')->put('badger/'.$newfilename, $file)){
            return 1;
        }
        else{
            return 0;
        }
    }

    // public function addToZip($filename){
        // $zipFileName = 'savedCheckins.zip';
        // $zip = new ZipArchive;
        // if ($zip->open('storage/app/public/'.$zipFileName, ZipArchive::CREATE) === TRUE) {
        //     $zip->addFile('storage/app/'.$filename, $filename);
        // }
        // $zip->close();
    // }

    public function deleteFile($filename){
        Storage::disk('local')->delete($filename);
    }

    public function createCheckin($data){
        $checkin = new Checkin;
        $checkin->rep_email = $data[1];
        $checkin->account_name = $data[2];
        $checkin->account_address = $data[3];
        $checkin->date = $data[4];
        $checkin->time = $data[5];
        $checkin->local_time = $data[6];
        $checkin->timezone = $data[7];
        $checkin->customer_id = $data[10];
        $checkin->type = $data[11];
        $checkin->estimate_number = $data[12];
        $checkin->estimate_total = $data[13];
        $checkin->po_number = $data[14];
        $checkin->po_total = $data[15];
        $checkin->note = json_encode($data[16]);
        if($checkin->save()){
            return 1;
        }
        else{
            return 0;
        }
    }

    

}
