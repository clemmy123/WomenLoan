<?php

$jamiiOrigins = array_values(array_filter(array_map(
    'trim',
    explode(',', (string) env(
        'JAMII_CORS_ORIGINS',
        'http://127.0.0.1:5173,http://127.0.0.1:5175,http://localhost:5173,http://localhost:5175'
    ))
)));

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

    'allowed_origins' => $jamiiOrigins !== [] ? $jamiiOrigins : ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Accept', 'Content-Type'],

    'exposed_headers' => [],

    'max_age' => 86400,

    'supports_credentials' => false,

];
