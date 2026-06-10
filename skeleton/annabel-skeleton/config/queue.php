<?php

return [
    'default' => env('QUEUE_CONNECTION', 'sync'),

    'connections' => [
        'sync' => [
            'driver' => 'sync',
        ],

        'database' => [
            'driver' => 'database',
            'table' => env('QUEUE_TABLE', 'jobs'),
            'failed_table' => env('QUEUE_FAILED_TABLE', 'failed_jobs'),
            'retry_after' => (int) env('QUEUE_RETRY_AFTER', 60),
            'max_attempts' => (int) env('QUEUE_MAX_ATTEMPTS', 3),
        ],

        'redis' => [
            'driver' => 'redis',
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'port' => (int) env('REDIS_PORT', 6379),
            'password' => env('REDIS_PASSWORD'),
            'database' => (int) env('REDIS_QUEUE_DB', 0),
            'timeout' => (float) env('REDIS_TIMEOUT', 2.0),
            'prefix' => env('QUEUE_REDIS_PREFIX', 'queue:'),
            'retry_after' => (int) env('QUEUE_RETRY_AFTER', 60),
            'max_attempts' => (int) env('QUEUE_MAX_ATTEMPTS', 3),
        ],
    ],

    'backoff' => (int) env('QUEUE_BACKOFF', 0),
    'timeout' => (int) env('QUEUE_TIMEOUT', 0),
];
