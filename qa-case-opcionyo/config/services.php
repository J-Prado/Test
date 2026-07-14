<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Stripe (Flow B) — sandbox keys go in .env
    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        // Milliseconds to auto-confirm a payment in fake mode (no STRIPE_SECRET).
        // Set to negative to require the webhook to activate the subscription.
        'auto_confirm_ms' => env('STRIPE_AUTO_CONFIRM_MS', 1500),
    ],

    // AWS Chime SDK Meetings (video)
    'chime' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('CHIME_REGION', env('AWS_DEFAULT_REGION', 'us-east-1')),
        // When false (default), a deterministic stub is returned instead of
        // calling real Chime — so the flow is testable without AWS creds.
        'enabled' => env('CHIME_ENABLED', false),
    ],

];
