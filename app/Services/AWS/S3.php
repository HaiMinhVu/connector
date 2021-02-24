<?php 

namespace App\Services\AWS;

use Storage;

class S3
{
	protected $service;

    public function __construct()
    {
        
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