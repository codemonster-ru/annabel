<?php

return [
    'providers' => [
        'defaults' => true,
        'disabled' => [],
        'extra' => [],
        'discover' => true,
        'path' => base_path('bootstrap/providers'),
        'packages' => [
            'discover' => true,
            'dont_discover' => [],
            'cache' => true,
            'cache_path' => base_path('bootstrap/cache/packages.php'),
        ],
    ],
    'routing' => [
        'attributes' => [
            'enabled' => true,
            'paths' => [
                base_path('app/Controllers'),
            ],
        ],
    ],
    'services' => [
        'enabled' => true,
        'paths' => [
            base_path('app'),
        ],
        'autoconfigure' => [],
    ],
];
