<?php

namespace App\Services\Badger;

use phpseclib\Net\SFTP;
use Illuminate\Support\Facades\File;

class Badger {

    const REMOTE_PATH = "dropbox/incoming/accounts";

    private $ftpClient;
    private $fileName;

    public function __construct()
    {
        $host = config('services.badger.host');
        $login = config('services.badger.login');
        $password = config('services.badger.password');
        $this->setupClient($host, $login, $password);
        $this->fileName = time();
    }

    private function setupClient($host, $login, $password)
    {
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
        return self::REMOTE_PATH."/{$this->fileName}.csv";
    }

}
