<?php

namespace App\Services\NetSuite\CustomList;

class CustomerStatus {

    private $response;

    public function __construct()
    {
        $this->init();
    }

    protected function init()
    {
        $this->response = [
            17 => "Lead - Unknown",
            25 => "Lead - Marketing",
            29 => "Lead - Not EMEA Region",
            26 => "Lead - Qualified",
            27 => "Lead - Qualified (buying from Distributor)",
            7 => "Lead - Qualified (interested)",
            18 => "Lead - Qualified (uninterested)",
            66 => "Lead - Unqualified",
            8 => "Prospect - Catalog/Line Review",
            14 => "Prospect - Closed Lost",
            9 => "Prospect - Identified Decision Makers",
            10 => "Prospect - Identifying needs",
            11 => "Prospect - In Negotiation",
            12 => "Prospect - Purchasing",
            13 => "Customer - Closed Won",
            15 => "Customer - Customer End user",
            19 => "Customer - Customer ID Needs",
            28 => "Customer - Customer Lost (Marketing)",
            22 => "Customer - Customer negotiation",
            21 => "Customer - Customer proposal",
            16 => "Customer - Lost Customer",
            23 => "Customer - Marketing",
            24 => "Customer - Marketing End User",
        ];
    }

    public function getRequest()
    {
        return $this->response;
    }
}
