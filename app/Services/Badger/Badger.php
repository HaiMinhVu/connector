<?php

namespace App\Services\Badger;

use phpseclib\Net\SFTP;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;
use App\Models\BadgerAccount;

class Badger {

    const REMOTE_UPLOAD_PATH = "dropbox/incoming/accounts";

    private $ftpClient;
    private $fileName;

    public function __construct()
    {
        $this->setupClient();
        $this->fileName = Carbon::now()->format('Y_m_d_Hi');
    }

    private function setupClient()
    {
        $host = config('services.badger.host');
        $login = config('services.badger.login');
        $password = config('services.badger.password');

        $this->ftpClient = new SFTP($host);

        if (!$this->ftpClient->login($login, $password)) {
        	throw new Exception('Login failed');
        }
    }

    public function export($data)
    {
        $this->createCSVFile($data);
        $this->uploadViaFTP();
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

}
