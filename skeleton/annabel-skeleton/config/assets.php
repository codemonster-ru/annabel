<?php

return [
    'vite' => [
        'hot_file' => base_path('public/hot'),
        'manifest' => 'public/build/manifest.json',
        'build_url' => '/build',
        'dev_server' => env('VITE_DEV_SERVER', 'http://localhost:5173'),
    ],
];
