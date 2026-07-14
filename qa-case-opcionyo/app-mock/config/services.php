<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    */

    'stripe' => [
        'key' => env('STRIPE_KEY'),                       // pk_test_... (publishable)
        'secret' => env('STRIPE_SECRET'),                 // sk_test_... (secret)
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'), // whsec_...
    ],

    'chime' => [
        'region' => env('CHIME_REGION', 'us-east-1'),
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
    ],

];
