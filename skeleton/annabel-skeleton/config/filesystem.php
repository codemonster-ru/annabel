<?php

return [
    'default' => 'local',

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => base_path('storage/app'),
        ],

        'public' => [
            'driver' => 'local',
            'root' => base_path('storage/app/public'),
            'url' => env('APP_URL', 'http://localhost') . '/storage',
        ],
    ],
];
