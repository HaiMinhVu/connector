<?php 

namespace App\Services;

use Storage;

class S3
{
	protected $service;

    public function __construct()
    {
        $this->setService();
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
	    $this->service->addHeader('keep_alive', false);
    }

    
	public function upload_file($file){   
		$file_name = $file->getClientOriginalName();
        try{
        	Storage::disk('s3')->put('badger/'.$file_name, file_get_contents($file));
        }catch(\Exception $e){
            return false;
        }  
    }
}