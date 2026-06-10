<?php

return [
    /*
     * "database", class name, or instance implementing UserProviderInterface.
     * When null, Annabel uses the in-memory users list below.
     */
    'provider' => null,

    /*
     * Class name or instance implementing PasswordHasherInterface.
     * When null, Annabel uses NativePasswordHasher.
     */
    'hasher' => null,

    'users' => [],
    'credential_key' => 'email',
    'database' => [
        'table' => 'users',
        'identifier_column' => 'id',
        'password_column' => 'password',
    ],
    'abilities' => [],
    'policies' => [],
    'session_key' => 'auth.user_id',
    'redirect_to' => env('AUTH_REDIRECT_TO', '/login'),
];
