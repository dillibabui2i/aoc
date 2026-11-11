<?php

return [
    /*
      |--------------------------------------------------------------------------
      | Right Signature config
      |--------------------------------------------------------------------------
      |
      */

    'allowedEndpoints' => [
        'documents',
        'sending_requests',
        'signers',
    ],

    'privateToken' => env('RIGHT_SIGNATURE_PRIVATE_TOKEN', ''),

    'url' => env('RIGHT_SIGNATURE_API_URL', ''),
];
