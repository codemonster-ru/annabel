<?php

return [
    'provider' => env('AUTH_PROVIDER'),
    'hasher' => null,
    'users' => [],
    'credential_key' => (string) env('AUTH_CREDENTIAL_KEY', 'email'),
    'database' => [
        'table' => (string) env('AUTH_TABLE', 'users'),
        'identifier_column' => (string) env('AUTH_IDENTIFIER_COLUMN', 'id'),
        'password_column' => (string) env('AUTH_PASSWORD_COLUMN', 'password'),
    ],
    'abilities' => [],
    'policies' => [],
    'session_key' => (string) env('AUTH_SESSION_KEY', 'auth.user_id'),
    'redirect_to' => env('AUTH_REDIRECT_TO', '/login'),
];
