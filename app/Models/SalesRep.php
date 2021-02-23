<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesRep extends Model {

    protected $table = 'eu_salesreps';
    protected $primaryKey = 'nsid';
    public $timestamps = false;

    protected $fillable = [
        'nsid',
        'email',
        'name',
        'active',
        'lastModifiedDate'
    ];

    public function updateLocalFromNetSuite()
    {

    }
}
