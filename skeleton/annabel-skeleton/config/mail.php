<?php

return [
    'default' => env('MAIL_MAILER', 'log'),

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
        'name' => env('MAIL_FROM_NAME', 'Annabel'),
    ],

    'mailers' => [
        'array' => [
            'transport' => 'array',
        ],

        'log' => [
            'transport' => 'log',
        ],

        'smtp' => [
            'transport' => 'smtp',
            'dsn' => env('MAILER_DSN', 'smtp://localhost:25'),
        ],

        'sendmail' => [
            'transport' => 'sendmail',
            'command' => env('MAIL_SENDMAIL_COMMAND', '/usr/sbin/sendmail -t -i'),
        ],
    ],
];
