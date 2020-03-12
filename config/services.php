<?php

return [

    'badger' => [
        'host' => env('BADGER_SFTP_HOST', 'ftp.badgermapping.com'),
        'login' => env('BADGER_LOGIN'),
        'password' => env('BADGER_PASSWORD')
    ],

    'netsuite' => [
        'endpoint' => env('NETSUITE_ENDPOINT', '2019_1'),
        'host' => env('NETSUITE_HOST', 'https://webservices.netsuite.com'),
        'email' => env('NETSUITE_EMAIL'),
        'password' => env('NETSUITE_PASSWORD'),
        'role' => env('NETSUITE_ROLE'),
        'account' => env('NETSUITE_ACCOUNT'),
        'app_id' => env('NETSUITE_APP_ID')
    ],

    'sellmark' => [
        'endpoint' => env('SLMK_API_ENDPOINT'),
        'token' => env('SLMK_API_TOKEN')
    ]

];
