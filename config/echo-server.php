<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Laravel Echo Server Configuration
    |--------------------------------------------------------------------------
    |
    | The configuration options set in this file will be passed directly to the
    | `laravel-echo-server` JSON file, this file will be loaded by the Laravel echo server during start up. This file
    | is created to the application root directory. The full set of possible options are documented at:
    | https://github.com/tlaverdure/laravel-echo-server
    |
    */
    'authHost' => env('ECHO_SERVER_AUTH_HOST', 'http://localhost:8000'),
    'authEndpoint' => env('ECHO_SERVER_AUTH_ENDPOINT', '/broadcasting/auth'),
    'clients' => env('ECHO_SERVER_CLIENTS', []),
    'database' => env('ECHO_SERVER_DB_PROVIDER', 'redis'),
    'databaseConfig' => [
        'redis' => [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'port' => env('REDIS_PORT', '6379'),
            'password' => env('REDIS_PASSWORD', 'root'),
            'keyPrefix' => env('REDIS_PREFIX', '')
        ],
        'sqlite' => [
            'databasePath' => env('SQLITE_DB_PATH', '/database/laravel-echo-server.sqlite'),
        ]
    ],
    'devMode' => env('ECHO_SERVER_DEBUG', true),
    'host' => env('ECHO_SERVER_HOST', null),
    'port' => env('ECHO_SERVER_PORT', '6001'),
    'protocol' => env('ECHO_SERVER_PROTO', 'http'),
    'socketio' => env('ECHO_SERVER_SOCKET_INSTANCE', (object) []),
    'sslCertPath' => env('ECHO_SERVER_SSL_CERT', ''),
    'sslKeyPath' => env('ECHO_SERVER_SSL_KEY', ''),
    'sslCertChainPath' => env('ECHO_SERVER_SSL_CHAIN', ''),
    'sslPassphrase' => env('ECHO_SERVER_SSL_PASS', ''),
    'subscribers' => [
        'http' => env('ECHO_SERVER_SUBSCRIBERS_HTTP', true),
        'redis' => env('ECHO_SERVER_SUBSCRIBERS_REDIS', true),
    ],
    'apiOriginAllow' => [
        'allowCors' => env('ECHO_SERVER_ALLOW_CORS', false),
        'allowOrigin' => env('ECHO_SERVER_CORS_ORIGIN', ''),
        'allowMethods' => env('ECHO_SERVER_CORS_METHODS', ''),
        'allowHeaders' => env('ECHO_SERVER_CORS_HEADERS', ''),
    ],
];
