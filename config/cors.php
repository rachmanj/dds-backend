<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:3000',
        'http://127.0.0.1:3000',
        'http://localhost',
        'http://localhost:8000',
        'http://127.0.0.1:8000',
        'http://103.23.198.4',  // Production frontend IP
        'https://103.23.198.4', // Production frontend IP with HTTPS
        env('FRONTEND_URL', 'http://localhost:3000')
    ],

    'allowed_origins_patterns' => [
        '/^http:\/\/103\.23\.198\.\d+$/',  // Allow any port on the production IP
        '/^https:\/\/103\.23\.198\.\d+$/', // Allow any port on the production IP with HTTPS
    ],

    'allowed_headers' => [
        'X-CSRF-TOKEN',
        'X-Requested-With',
        'Content-Type',
        'Accept',
        'Authorization',
        'X-Handle-As-Json',
        'Origin',
        'Access-Control-Request-Method',
        'Access-Control-Request-Headers',
    ],

    'exposed_headers' => ['*'],

    'max_age' => 0,

    'supports_credentials' => true,

];
