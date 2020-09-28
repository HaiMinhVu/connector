<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Storage;
use phpseclib\Net\SFTP;
use App\Models\{
    Checkin,    
};

class GetCheckins extends Command
{
    const REMOTE_CHECKINS_PATH = "dropbox/outgoing/checkins/";

    protected $signature = 'get:checkins';
    protected $description = 'download check-ins from badger to local';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $connection = $this->badger_connection();
        $this->getCheckins($connection);
    }    

    private static function badger_connection (){
        $host = config('services.badger.host');
        $login = config('services.badger.login');
        $password = config('services.badger.password');
        $connection = new SFTP($host);
        try{
            $connection->login($login, $password);
        }catch(\Exception $e) {
            return $e->getMessage();
        }
        return $connection;
    }

    public function getCheckins($connection){
        $files = $connection->nlist(self::REMOTE_CHECKINS_PATH);
        foreach ($files as $filename) {
            if(strpos($filename,'.csv')){
                $this->downloadFromRemote($connection, $filename);
            }
        }
    }

    public function downloadFromRemote($connection, $filename){
        try{
            if(Storage::disk('local')->put($filename, $connection->get(self::REMOTE_CHECKINS_PATH.$filename))){
                // $this->deleteOnRemote($connection, $filename);
            }
        } catch(\Exception $e) {
            return $e->getMessage();
        }
    }

    public function deleteOnRemote($connection, $filename){
        $connection->delete(self::REMOTE_CHECKINS_PATH.$filename, false);
    }

    public function uploadToRemote($connection, $filename){
        $connection->put(self::REMOTE_CHECKINS_PATH.$filename, Storage::disk('local')->get($filename));
    }
}
