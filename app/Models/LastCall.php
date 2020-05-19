<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LastCall extends Model {
    // protected $connection = 'nsconnector';

    protected $table = 'lastCall';

    public $timestamps = false;

    // protected $fillable = [
    //     ‘title’,
    //     ‘description’,
    //     ‘body’
    // ];
}
