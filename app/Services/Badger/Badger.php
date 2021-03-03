<?php

namespace App\Services\Badger;

use phpseclib\Net\SFTP;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;
use App\Models\{
    BadgerAccount,
    BadgerCheckin
};
use Storage;

class Badger {

    const REMOTE_UPLOAD_PATH = "dropbox/incoming/accounts";
    const REMOTE_DOWNLOAD_PATH = "dropbox/outgoing/checkins/";

    private $ftpClient;
    private $fileName;

    public function __construct()
    {
        $this->setupClient();
        $this->fileName = Carbon::now()->format('Y_m_d');
    }

    private function setupClient()
    {
        $this->ftpClient = self::getClient();
    }

    private static function getClient()
    {
        $host = config('services.badger.host');
        $login = config('services.badger.login');
        $password = config('services.badger.password');

        $ftpClient = new SFTP($host);

        if (!$ftpClient->login($login, $password)) {
        	throw new Exception('Login failed');
        }

        return $ftpClient;
    }

    public function export($data)
    {
        if(!empty($data)){
            $this->createCSVFile($data);
            $this->uploadViaFTP();
            $this->deleteFile("{$this->fileName}.csv");
        }
    }

    public function exportCustomers($fromDate = null)
    {
        try {
            $date = \Carbon\Carbon::parse($fromDate);
        } catch(\Exception $e) {
            $date = \Carbon\Carbon::now()->subDay();
        }
        $badgerAccounts = BadgerAccount::where('lastModifiedDate', '>=', $date->toDateTimeString())->get();
        $badgerAccounts = $badgerAccounts->map(function($badgerAccount){
            return $badgerAccount->formatForBadger();
        });
        $this->export($badgerAccounts->toArray());
    }

    private function createCSVFile($data)
    {
        $data = collect($data);
        $file = fopen($this->localFile(), 'w');

        $keys = array_keys($data->first());

        fputcsv($file, $keys);

        $data->map(function($row) use ($file) {
            fputcsv($file, $row);
        });
        fclose($file);
    }

    private function uploadViaFTP()
    {
        try {
            $file = File::get($this->localFile());
            $this->ftpClient->put($this->remoteFile(), $file);
        } catch (\Exception $e) {
            dd($e->getMessage());
        }
    }

    private function localFile()
    {
        return storage_path()."/app/{$this->fileName}.csv";
    }

    private function remoteFile()
    {
        return self::REMOTE_UPLOAD_PATH."/{$this->fileName}.csv";
    }

    private function deleteFile($filename)
    {
        Storage::disk('local')->delete($filename);
    }




    /********************* CHECK-INS **********************/
    public function downloadCheckins(){
        $files = $this->ftpClient->nlist(self::REMOTE_DOWNLOAD_PATH);
        foreach ($files as $filename) {
            if(strpos($filename,'.csv')){
                $this->downloadFromRemote($filename);
            }
        }
    }

    private function downloadFromRemote($filename){
        try{
            if(Storage::disk('local')->put($filename, $this->ftpClient->get(self::REMOTE_DOWNLOAD_PATH.$filename))){
                $this->deleteOnRemote($filename);
            }
        } catch(\Exception $e) {
            return $e->getMessage();
        }
    }

    private function deleteOnRemote($filename){
        $this->ftpClient->delete(self::REMOTE_DOWNLOAD_PATH.$filename, false)){
    }

    private function getAllFilenames(){
        return array_filter(Storage::disk('local')->files(), function ($item) {
            return strpos($item, '.csv');
        });
    }

    public function insertCheckins(){
        $filenames = $this->getAllFilenames();
        foreach ($filenames as $filename) {
            $file = Storage::disk('local')->get($filename);
            $this->processFile($filename);
        }
    }

    private function processFile($filename){
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

    private function createCheckin($data){
        $checkin = new BadgerCheckin;
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
