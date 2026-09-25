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

    'jamii' => [
        // Keep false until new Jamii SSO instructions are applied.
        'sso_enabled' => (bool) env('JAMII_SSO_ENABLED', false),
        // Prefer JUMUISHI_URL so live/local central host is not hardcoded separately.
        'shell_url' => rtrim((string) env(
            'JAMII_SHELL_URL',
            env('JUMUISHI_URL', 'http://127.0.0.1:8000')
        ), '/'),
        'cors_origins' => (static function (): string {
            $jumuishi = rtrim((string) env('JUMUISHI_URL', ''), '/');
            $configured = trim((string) env('JAMII_CORS_ORIGINS', ''));
            $appHost = strtolower((string) parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST));
            $localApp = in_array($appHost, ['127.0.0.1', 'localhost', '::1', ''], true)
                || str_ends_with($appHost, '.localhost')
                || str_ends_with($appHost, '.test')
                || str_ends_with($appHost, '.local');
            $origins = $configured !== ''
                ? array_filter(array_map('trim', explode(',', $configured)))
                : ($localApp ? [
                    'http://127.0.0.1:8000',
                    'http://localhost:8000',
                ] : []);

            if ($jumuishi !== '') {
                array_unshift($origins, $jumuishi);
            }

            return implode(',', array_values(array_unique($origins)));
        })(),
        'sso_ticket_ttl' => (int) env('JAMII_SSO_TICKET_TTL', 60),
    ],

];
