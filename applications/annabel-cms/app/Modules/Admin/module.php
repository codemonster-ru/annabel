<?php

return [
    'name' => 'Admin',
    'version' => '1.0.0',
    'dependencies' => ['Core', 'Auth'],
    'routes' => 'routes/web.php',
    'views' => 'views',
    'assets' => [
        'vite_config' => 'vite.config.js',
        'manifest' => 'public/admin/assets/.vite/manifest.json',
        'public_path' => '/admin/assets',
    ],
];
