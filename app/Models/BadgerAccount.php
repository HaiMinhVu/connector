<?php
namespace App\Models;

use Illuminate\Database\Eloquent\{Builder, Model};

class BadgerAccount extends Model {

    const CHANGE_TYPE_INSERT = 'I';
    const CHANGE_TYPE_UPDATE = 'U';
    const CHANGE_TYPE_DELETE = 'D';

    protected $primaryKey = 'nsid';

    // protected $connection = 'badgeraccounts';

    protected $table = 'eu_badgeraccounts';

    public $timestamps = false;

    protected $fillable = [
        'nsid',
        'company_name',
        'sale_rep',
        'status',
        'territory',
        'shipping_address1',
        'shipping_address2',
        'shipping_city',
        'shipping_country',
        'shipping_zip',
        'primary_contact',
        'phone',
        'email',
        'fax',
        'alt_contact',
        'office_phone',
        'license_required',
        'billing_address1',
        'billing_address2',
        'billing_city',
        'billing_state',
        'billing_zip',
        'billing_country',
        'account_category',
        'bg_tax_number',
        'business_model',
        'change_type'
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

    public function formatForBadger()
    {
        return [
            'nsid' => $this->change_type,
            'company_name' => $this->company_name,
            'sale_rep' => $this->sale_rep,
            'status' => $this->status,
            'territory' => $this->territory,
            'shipping_address1' => $this->shipping_address1,
            'shipping_address2' => $this->shipping_address2,
            'shipping_city' => $this->shipping_city,
            'shipping_country' => $this->shipping_country,
            'shipping_zip' => $this->shipping_zip,
            'primary_contact' => $this->primary_contact,
            'phone' => $this->phone,
            'email' => $this->email,
            'fax' => $this->fax,
            'alt_contact' => $this->alt_contact,
            'office_phone' => $this->office_phone,
            'license_required' => $this->license_required,
            'billing_address1' => $this->billing_address1,
            'billing_address2' => $this->billing_address2,
            'billing_city' => $this->billing_city,
            'billing_state' => $this->billing_state,
            'billing_zip' => $this->billing_zip,
            'billing_country' => $this->billing_country,
            'account_category' => $this->account_category,
            'bg_tax_number' => $this->bg_tax_number,
            'business_model' => $this->business_model,
            'change_type' => $this->change_type,
            'NetSuiteUrl' => $this->getNetSuiteUrl()
        ];
    }
}
