<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesRep extends Model {
    protected $primaryKey = 'nsid';

    // protected $connection = 'salesreps';  // comment this line when using aws rds

    protected $table = 'salesreps';

    public $timestamps = false;

    protected $fillable = [
        'email',
        'lastModifiedDate',
        'first',
        'last',
        'active',
        'entityId',
        'nsid',
        'tick',
        'status',
        'crc',
        'lastcrc',
        'ismod'
    ];

    public function updateLocalFromNetSuite()
    {

    }
}
