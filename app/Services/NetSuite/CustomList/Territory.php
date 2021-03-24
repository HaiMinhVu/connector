<?php

namespace App\Services\NetSuite\CustomList;

class Territory {

    private $territories;

    public function __construct()
    {
        $this->init();
    }

    private function init()
    {
        $this->territories = [
            1 => 'End Users',
            24 => 'Digital Team',
            25 => 'Thermal Specialist',
            26 => 'Ozarks',
            27 => 'Texas',
            28 => 'Texas Field Sales',
            29 => 'North Central',
            30 => 'Deep South',
            31 => 'Mountain West',
            32 => 'Great Lakes',
            33 => 'Gulf Coast',
            34 => 'North East',
            36 => 'West Coast',
            37 => 'West Coast Field Sales',
            38 => 'Appalachia',
            39 => 'Appalachia Field Sales',
            41 => 'Latin America',
            43 => 'LE Southwest',
            45 => 'LE South Central',
            46 => 'LE North Central',
            47 => 'LE Greater Lakes',
            49 => 'LE Northeast',
            50 => 'LE Southeast',
            51 => 'Sellmark OOD',
            52 => 'Southeastern Field Sales',
            53 => 'NE Great Lakes Field Sales',
            54 => 'LE Digital',
            55 => 'Western Field Sales',
            56 => 'Midwest Field Sales',
            57 => 'Rep Group',
            58 => 'Canada Pacific',
            59 => 'Marketing',
            60 => 'Pulsar DNS',
            61 => 'OEM',
            62 => 'South Central',
            63 => 'Coastal',
            64 => 'Mid West',
            65 => 'South West',
            66 => 'Central',
            67 => 'BulletSafe Wholesale',
            68 => 'National',
            69 => 'Distributor',
        ];
    }

    public function getTerritory($nsid)
    {
        return $this->territories[$nsid];
    }

}
