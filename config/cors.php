<?php

$jumuishiOrigin = rtrim((string) env('JUMUISHI_URL', ''), '/');
$configuredCors = trim((string) env('JAMII_CORS_ORIGINS', ''));
$appHost = strtolower((string) parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST));
$localApp = in_array($appHost, ['127.0.0.1', 'localhost', '::1', ''], true)
    || str_ends_with($appHost, '.localhost')
    || str_ends_with($appHost, '.test')
    || str_ends_with($appHost, '.local');
$jamiiOrigins = array_values(array_filter(array_map(
    'trim',
    explode(',', $configuredCors !== ''
        ? $configuredCors
        : ($localApp ? 'http://127.0.0.1:8000,http://localhost:8000' : ''))
)));
if ($jumuishiOrigin !== '') {
    array_unshift($jamiiOrigins, $jumuishiOrigin);
    $jamiiOrigins = array_values(array_unique($jamiiOrigins));
}

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Shell (Jamii) calls WDF public/login APIs from a different origin.
    | Origins come from JAMII_CORS_ORIGINS (same list as services.jamii).
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'OPTIONS'],

    'allowed_origins' => $jamiiOrigins,

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Accept', 'Content-Type'],

    'exposed_headers' => [],

    'max_age' => 86400,

    'supports_credentials' => false,

];
