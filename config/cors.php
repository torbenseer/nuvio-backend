<?php

$allowedOrigins = array_filter(array_map('trim', explode(',', env(
    'CORS_ALLOWED_ORIGINS',
    env('FRONTEND_URL', 'http://localhost:3000'),
))));

return [

    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
        'login',
        'logout',
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values($allowedOrigins),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
