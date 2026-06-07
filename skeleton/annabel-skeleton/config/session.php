<?php

$sessionKey = static function (string $name): ?string {
    $value = env($name);

    return is_string($value) && $value !== '' ? $value : null;
};

$path = env('SESSION_PATH');
$encryptionKey = $sessionKey('SESSION_ENCRYPTION_KEY');
$previousEncryptionKey = $sessionKey('SESSION_PREVIOUS_ENCRYPTION_KEY');

return [
    'driver' => (string) env('SESSION_DRIVER', 'file'),
    'path' => is_string($path) && $path !== ''
        ? $path
        : base_path('storage/sessions'),
    'cookie' => [
        'secure' => (bool) env('SESSION_COOKIE_SECURE', false, true),
        'httponly' => true,
        'samesite' => (string) env('SESSION_COOKIE_SAME_SITE', 'Lax'),
        'lifetime' => (int) env('SESSION_COOKIE_LIFETIME', 86400),
        'path' => '/',
    ],
    'encryption' => $encryptionKey === null ? [] : [
        'key' => $encryptionKey,
        'previous_keys' => array_values(array_filter([$previousEncryptionKey])),
        'allow_plaintext' => (bool) env('SESSION_ALLOW_PLAINTEXT', true, true),
    ],
    'redis' => [
        'host' => (string) env('SESSION_REDIS_HOST', '127.0.0.1'),
        'port' => (int) env('SESSION_REDIS_PORT', 6379),
        'password' => env('SESSION_REDIS_PASSWORD'),
        'database' => (int) env('SESSION_REDIS_DATABASE', 0),
        'timeout' => (float) env('SESSION_REDIS_TIMEOUT', 2),
        'prefix' => (string) env('SESSION_REDIS_PREFIX', 'annabel_session:'),
        'ttl' => (int) env('SESSION_REDIS_TTL', 86400),
    ],
];
