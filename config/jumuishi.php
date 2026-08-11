<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Jumuishi central platform (module integration)
    |--------------------------------------------------------------------------
    |
    | When enabled, WDF does not own login, registration, or password reset.
    | Guests are sent to Jumuishi; local routes remain as compatibility redirects.
    |
    */

    'enabled' => (bool) env('JUMUISHI_ENABLED', true),

    'url' => rtrim((string) env('JUMUISHI_URL', 'http://127.0.0.1:8000'), '/'),

    'module_path' => (string) env('JUMUISHI_MODULE_PATH', 'women-loans'),

    'api_secret' => env('JUMUISHI_API_SECRET'),

    'platform_secret' => env('JUMUISHI_PLATFORM_SECRET'),

    'sso_start_path' => (string) env('JUMUISHI_SSO_START_PATH', '/sso/start'),

    'sso_exchange_path' => (string) env('JUMUISHI_SSO_EXCHANGE_PATH', '/api/internal/sso/exchange'),

    'central_logout_path' => (string) env('JUMUISHI_CENTRAL_LOGOUT_PATH', '/central-logout'),

    'password_path' => (string) env('JUMUISHI_PASSWORD_PATH', '/profile'),

    'forgot_password_path' => (string) env('JUMUISHI_FORGOT_PASSWORD_PATH', '/forgot-password'),

];
