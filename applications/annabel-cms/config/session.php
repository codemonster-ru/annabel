<?php

return [
    'driver' => (string) env('SESSION_DRIVER', 'file'),
    'path' => (string) env('SESSION_PATH', dirname(__DIR__) . '/storage/sessions'),
    'cookie' => [
        'secure' => (bool) env('SESSION_COOKIE_SECURE', false, true),
        'httponly' => true,
        'samesite' => (string) env('SESSION_COOKIE_SAME_SITE', 'Lax'),
        'lifetime' => (int) env('SESSION_COOKIE_LIFETIME', 86400),
        'path' => '/',
    ],
    'encryption' => [
        'key' => env('SESSION_ENCRYPTION_KEY'),
        'previous_keys' => [env('SESSION_PREVIOUS_ENCRYPTION_KEY')],
        'allow_plaintext' => (bool) env('SESSION_ALLOW_PLAINTEXT', false, true),
    ],
    'redis' => [
        'host' => (string) env('SESSION_REDIS_HOST', 'redis'),
        'port' => (int) env('SESSION_REDIS_PORT', 6379),
        'password' => env('SESSION_REDIS_PASSWORD'),
        'database' => (int) env('SESSION_REDIS_DATABASE', 0),
        'timeout' => (float) env('SESSION_REDIS_TIMEOUT', 2),
        'prefix' => (string) env('SESSION_REDIS_PREFIX', 'annabel_cms_session:'),
        'ttl' => (int) env('SESSION_REDIS_TTL', 86400),
    ],
];
