<?php

$csv = static function (string $key, array $default = []): array {
    $raw = env($key);

    if (!is_string($raw)) {
        return $default;
    }

    $raw = trim($raw);

    if ($raw === '') {
        return [];
    }

    return array_values(array_filter(array_map('trim', explode(',', $raw)), static fn($v) => $v !== ''));
};

return [
    'csrf' => [
        'enabled' => (bool) env('SECURITY_CSRF_ENABLED', true),
        'add_to_kernel' => (bool) env('SECURITY_CSRF_ADD_TO_KERNEL', true),
        'verify_json' => (bool) env('SECURITY_CSRF_VERIFY_JSON', false),
        'input_key' => (string) env('SECURITY_CSRF_INPUT_KEY', '_token'),
        'except_methods' => $csv('SECURITY_CSRF_EXCEPT_METHODS', ['GET', 'HEAD', 'OPTIONS']),
        'except' => $csv('SECURITY_CSRF_EXCEPT', ['api/*']),
    ],
    'throttle' => [
        'enabled' => (bool) env('SECURITY_THROTTLE_ENABLED', true),
        'add_to_kernel' => (bool) env('SECURITY_THROTTLE_ADD_TO_KERNEL', false),
        'max_attempts' => (int) env('SECURITY_THROTTLE_MAX_ATTEMPTS', 60),
        'decay_seconds' => (int) env('SECURITY_THROTTLE_DECAY_SECONDS', 60),
        'except' => $csv('SECURITY_THROTTLE_EXCEPT', []),
    ],
];
