<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BadgerAccount extends Model {

    const CHANGE_TYPE_INSERT = 'I';
    const CHANGE_TYPE_UPDATE = 'U';
    const CHANGE_TYPE_DELETE = 'D';

    protected $primaryKey = 'nsid';

    protected $connection = 'badgeraccounts';

    protected $table = 'badgeraccounts';

    public $timestamps = false;

    protected $fillable = [
        '_Name',
        '_Address',
        '_Phone',
        '_Notes',
        'nsid',
        '_AccountOwner',
        'Business Email',
        'Contact Name',
        'Contact Email',
        'is Person',
        'Status',
        'url',
        'category',
        'territory',
        '_LatLong',
        'src',
        'tick',
        'active',
        'lastModifiedDate',
        '_ChangeType',
        'crc',
        'lastcrc',
        'ismod'
    ];

    protected $guarded = [
        'src'
    ];
}
