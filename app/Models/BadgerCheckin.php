<?php
namespace App\Models;

use Illuminate\Database\Eloquent\{Builder, Model};

class BadgerCheckin extends Model {

    protected $table = 'eu_badgercheckins';
    protected $fillable = [
    	'rep_email', 
    	'account_name', 
    	'account_address', 
    	'date', 
    	'time', 
    	'local_time', 
    	'timezone', 
    	'type', 
    	'comments',
    	'customer_id', 
    	'decision_maker_name', 
    	'next_step',  
    	'note', 
    	'is_processed'
    ];
    public $timestamps = false;
    
}