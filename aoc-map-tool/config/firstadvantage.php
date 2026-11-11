<?php

return [
    'local' => [
        'url' => 'https://rest-uat.fadv.com/api/v1.1',
        'apiKey' => env('FA_API_LOCAL_KEY'),
        'account' => [
            'texas' => env('FA_API_LOCAL_ACCOUNT_KEY'),
            'mckinney' => env('FA_API_LOCAL_MC_ACCOUNT_KEY')
        ],
        'webhook' => 'http://aoc.loc/fi/webhook'
    ],
    'testing' => [
        'url' => 'https://rest-uat.fadv.com/api/v1.1',
        'apiKey' => env('FA_API_LOCAL_KEY'),
        'account' => [
            'texas' => env('FA_API_LOCAL_ACCOUNT_KEY'),
            'mckinney' => env('FA_API_LOCAL_MC_ACCOUNT_KEY')
        ],
        'webhook' => 'https://test2.angelsofcareapp.com/fi/webhook'
    ],
    'staging' => [
        'url' => env('FA_API_URL', 'https://rest-uat.fadv.com/api/v1.1'),
        'apiKey' => env('FA_API_LOCAL_KEY'),
        'account' => [
            'texas' => env('FA_API_LOCAL_ACCOUNT_KEY'),
            'mckinney' => env('FA_API_LOCAL_MC_ACCOUNT_KEY')
        ],
        'webhook' => 'https://test.angelsofcareapp.com/fi/webhook'
    ],
    'production' => [
        'url' => env('FA_API_URL', 'https://rest.fadv.com/api/v1.1'), // not yet
        'apiKey' =>  env('FA_API_PROD_KEY'), // we don't have yet
        'account' => [
            'texas' => env('FA_API_PROD_ACCOUNT_KEY'), // we don't have yet
            'mckinney' => env('FA_API_PROD_MC_ACCOUNT_KEY') // we don't have yet
        ],
        'webhook' => 'https://angelsofcareapp.com/fi/webhook'
    ]
];
