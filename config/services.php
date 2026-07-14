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

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'nida' => [
        'enabled' => (bool) env('NIDA_ENABLED', true),
        'driver' => env('NIDA_DRIVER', 'fake'),
        'base_url' => env('NIDA_BASE_URL'),
        'user_id' => env('NIDA_USER_ID'),
        'challenge_ttl' => (int) env('NIDA_CHALLENGE_TTL', 300),
        'verified_ttl' => (int) env('NIDA_VERIFIED_TTL', 600),
    ],

];
