<?php

return [
    'csrf' => [
        'enabled' => env('SECURITY_CSRF_ENABLED', true),
        'add_to_kernel' => env('SECURITY_CSRF_ADD_TO_KERNEL', true),
        'verify_json' => env('SECURITY_CSRF_VERIFY_JSON', false),
        'input_key' => env('SECURITY_CSRF_INPUT_KEY', '_token'),
        'except_methods' => ['GET', 'HEAD', 'OPTIONS'],
        'except' => ['api/*'],
    ],
    'throttle' => [
        'enabled' => env('SECURITY_THROTTLE_ENABLED', true),
        'add_to_kernel' => env('SECURITY_THROTTLE_ADD_TO_KERNEL', true),
        'max_attempts' => (int) env('SECURITY_THROTTLE_MAX_ATTEMPTS', 60),
        'decay_seconds' => (int) env('SECURITY_THROTTLE_DECAY_SECONDS', 60),
        'storage' => env('SECURITY_THROTTLE_STORAGE', 'session'),
        'connection' => env('SECURITY_THROTTLE_CONNECTION', null),
        'table' => env('SECURITY_THROTTLE_TABLE', 'throttle_requests'),
        'redis' => env('SECURITY_THROTTLE_REDIS', null),
        'prefix' => env('SECURITY_THROTTLE_PREFIX', 'throttle:'),
        'presets' => [
            'login' => [
                'ip' => [
                    'max_attempts' => (int) env('SECURITY_THROTTLE_LOGIN_IP_MAX_ATTEMPTS', 60),
                    'decay_seconds' => (int) env('SECURITY_THROTTLE_LOGIN_IP_DECAY_SECONDS', 60),
                ],
                'account' => [
                    'max_attempts' => (int) env('SECURITY_THROTTLE_LOGIN_ACCOUNT_MAX_ATTEMPTS', 5),
                    'decay_seconds' => (int) env('SECURITY_THROTTLE_LOGIN_ACCOUNT_DECAY_SECONDS', 300),
                    'field' => env('SECURITY_THROTTLE_LOGIN_ACCOUNT_FIELD', 'email'),
                ],
            ],
            'api' => [
                'max_attempts' => (int) env('SECURITY_THROTTLE_API_MAX_ATTEMPTS', 120),
                'decay_seconds' => (int) env('SECURITY_THROTTLE_API_DECAY_SECONDS', 60),
            ],
        ],
        'except' => [],
        'trusted_proxies' => array_filter(array_map(
            'trim',
            explode(',', (string) env('SECURITY_TRUSTED_PROXIES', '')),
        )),
    ],
];
