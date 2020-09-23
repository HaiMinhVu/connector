<?php
namespace App\Models;

use Illuminate\Database\Eloquent\{Builder, Model};

class BadgerAccount extends Model {

    const CHANGE_TYPE_INSERT = 'I';
    const CHANGE_TYPE_UPDATE = 'U';
    const CHANGE_TYPE_DELETE = 'D';

    protected $primaryKey = 'nsid';

    // protected $connection = 'badgeraccounts'; // comment this line when using aws rds

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

    protected $hidden = [
        'crc',
        'lastcrc',
        'src'
    ];

    /**
    * Scope a query to only include accounts that are listed as a person.
    *
    * @param  \Illuminate\Database\Eloquent\Builder  $query
    * @return \Illuminate\Database\Eloquent\Builder
    */
    public function scopeIsPerson($query) {
        return $query->where('is Person', '1');
    }

    public function getNetSuiteUrl()
    {
        return "https://system.na1.netsuite.com/app/common/entity/custjob.nl?id={$this->nsid}";
    }

    public function getStage()
    {
        $status = explode("-", $this->Status);
        return trim($status[0]);
    }

    public function formatForBadger()
    {
        if($this->getAttribute("is Person")) dd($this);
        return [
            "_ChangeType" => $this->_ChangeType,
            "_Name" => 	$this->_Name,
            "_Address" => $this->_Address,
            "_Phone" =>	$this->_Phone,
            "_Notes" =>	$this->_Notes,
            "nsid" => $this->nsid,
            "_AccountOwner" => $this->_AccountOwner,
            "Business Email" => $this->getAttribute("Business Email"),
            "Contact Name" => $this->getAttribute("Contact Name"),
            "Contact Email" => $this->getAttribute("Contact Email"),
            "is Person" => $this->getAttribute("is Person"),
            "Status" =>	$this->Status,
            "url" => $this->url,
            "category" => $this->category,
            "territory" => $this->territory,
            "NetSuite" => $this->getNetSuiteUrl(),
            "Stage" => $this->getStage()
        ];
    }
}
