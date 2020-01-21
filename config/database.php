<?php

return [

    'connections' => [

        'badgeraccounts' => [
            'driver' => 'sqlite',
            'database' => env('DB_BADGERACCOUNTS_LOCATION')
        ],

        'custom_entity_fields' => [
            'driver' => 'sqlite',
            'database' => env('DB_CUSTOMENTITYFIELDS_LOCATION')
        ],

        'nsconnector' => [
            'driver' => 'sqlite',
            'database' => env('DB_NSCONNECTOR_LOCATION')
        ],

        'salesreps' => [
            'driver' => 'sqlite',
            'database' => env('DB_SALESREPS_LOCATION')
        ]

    ]

];
