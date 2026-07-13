<?php

use Codemonster\Cms\Modules\Admin\ModuleServiceProvider;

return [
    'name' => 'Admin',
    'version' => '1.0.0',
    'dependencies' => ['Core', 'Auth'],
    'provider' => ModuleServiceProvider::class,
    'routes' => 'routes/web.php',
    'views' => 'views',
    'assets' => [
        'vite_config' => 'vite.config.js',
        'manifest' => 'public/admin/assets/.vite/manifest.json',
        'public_path' => '/admin/assets',
    ],
    'admin' => [
        'navigation' => [
            [
                'id' => 'dashboard',
                'label' => 'Dashboard',
                'href' => '/admin',
                'order' => 100,
            ],
            [
                'id' => 'settings',
                'label' => 'Settings',
                'order' => 900,
            ],
        ],
    ],
];
