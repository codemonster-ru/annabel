<?php

return [
    'csrf' => [
        'verify_json' => (bool) env('SECURITY_CSRF_VERIFY_JSON', true, true),
        'input_key' => (string) env('SECURITY_CSRF_INPUT_KEY', '_token'),
        'except_methods' => ['GET', 'HEAD', 'OPTIONS'],
        'except' => ['api/*'],
    ],
    'throttle' => [
        'max_attempts' => (int) env('SECURITY_THROTTLE_MAX_ATTEMPTS', 60),
        'decay_seconds' => (int) env('SECURITY_THROTTLE_DECAY_SECONDS', 60),
        'except' => [],
    ],
];
