<?php

$cachePath = env('CACHE_PATH');

return [
    'default' => env('CACHE_STORE', 'file'),
    'stores' => [
        'file' => [
            'driver' => 'file',
            'path' => is_string($cachePath) && $cachePath !== ''
                ? $cachePath
                : base_path('storage/cache'),
        ],
        'array' => [
            'driver' => 'array',
        ],
        'redis' => [
            'driver' => 'redis',
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'port' => (int) env('REDIS_PORT', 6379),
            'password' => env('REDIS_PASSWORD'),
            'database' => (int) env('REDIS_CACHE_DB', 0),
            'timeout' => (float) env('REDIS_TIMEOUT', 2.0),
            'prefix' => env('CACHE_PREFIX', 'cache:'),
        ],
    ],
];
