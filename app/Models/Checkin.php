<?php
namespace App\Models;

use Illuminate\Database\Eloquent\{Builder, Model};

class Checkin extends Model {

    protected $table = 'checkins';
    protected $fillable = ['rep_email', 'account_name', 'account_address', 'date', 'time', 'local_time', 'timezone', 'type', 'customer_id', 'estimate_number', 'estimate_total', 'po_number', 'po_total', 'note', 'is_processed'];
    public $timestamps = false;
    
}
