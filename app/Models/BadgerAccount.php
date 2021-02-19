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

    // public $timestamps = false;

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
            'Internal ID' => $this->nsid,
            'Company Name' => $this->company_name,
            'Sales Rep' => $this->sale_rep,
            'Status' => $this->status,
            'Territory' => $this->territory,
            'Shipping Address 1' => $this->shipping_address1,
            'Shipping Address 2' => $this->shipping_address2,
            'Shipping City' => $this->shipping_city,
            'Shipping Country' => $this->shipping_country,
            'Shipping Zip' => $this->shipping_zip,
            'Primary Contact' => $this->primary_contact,
            'Phone' => $this->phone,
            'Email' => $this->email,
            'Fax' => $this->fax,
            'Alt. Contact' => $this->alt_contact,
            'Office Phone' => $this->office_phone,
            'License Required' => $this->license_required,
            'Billing Address 1' => $this->billing_address1,
            'Billing Address 2' => $this->billing_address2,
            'Billing City' => $this->billing_city,
            'Billing State/Province' => $this->billing_state,
            'Billing Zip' => $this->billing_zip,
            'Billing Country' => $this->billing_country,
            'Account Category' => $this->account_category,
            'BG Tax Number' => $this->bg_tax_number,
            'Business Model' => $this->business_model,
            'Change Type' => $this->change_type,
        ];
    }
}
