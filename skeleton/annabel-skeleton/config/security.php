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
        'except' => [],
    ],
];
