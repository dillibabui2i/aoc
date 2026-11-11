<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OTP Defaults
    |--------------------------------------------------------------------------
    |
    | This option controls the default OTP parameters
    |
    */

    'refactor_enabled' => env('OTP_REFACTOR_ENABLED', false),
    
    'ttl' => 300,

    'resendLimit' => 3,
    'resendDelaySeconds' => 30,
    'retryLimit' => 3,

    'cooldownEventsThreshold' => 5,
    'cooldownMinutes' => 15,
];
