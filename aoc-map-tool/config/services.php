<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'first-advantage' => [
        'accounts' => [
            'url' => 'https://rest-uat.fadv.com/api/v1.1/accounts/%s/details' //todo move to env
        ],
        'packages' => [
            'url' => 'https://rest-uat.fadv.com/api/v1.1/accounts/%s/packages'  //todo move to env
        ]
    ],

    // NLM Clinical Tables ICD-10-CM search API (PD-3498's Diagnosis Code typeahead). No API key: it's a
    // free public search endpoint, not a full-catalog export.
    'icd10' => [
        'search_url' => env('ICD10_SEARCH_URL', 'https://clinicaltables.nlm.nih.gov/api/icd10cm/v3/search'),
    ],

];
