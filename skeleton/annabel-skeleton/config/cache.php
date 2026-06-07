<?php

$cachePath = env('CACHE_PATH');

return [
    'default' => env('CACHE_STORE', 'file'),
    'stores' => [
        'file' => [
            'path' => is_string($cachePath) && $cachePath !== ''
                ? $cachePath
                : base_path('storage/cache'),
        ],
        'array' => [],
    ],
];
