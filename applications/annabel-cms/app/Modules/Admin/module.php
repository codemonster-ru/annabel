<?php

use Codemonster\Cms\Modules\Admin\ModuleServiceProvider;

return [
    'name' => 'Admin',
    'version' => '1.0.0',
    'system' => true,
    'author' => [
        'name' => 'Codemonster',
        'url' => 'https://codemonster.net',
    ],
    'dependencies' => ['Core', 'Auth', 'Settings'],
    'provider' => ModuleServiceProvider::class,
    'routes' => 'routes/web.php',
    'views' => 'views',
    'permissions' => [
        ['code' => 'admin.access', 'name' => 'Access the administration panel', 'category' => 'Administration', 'sort_order' => 100],
    ],
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
                'icon' => 'house',
                'href' => '/admin',
                'permission' => 'admin.access',
                'order' => 100,
            ],
            [
                'id' => 'settings',
                'label' => 'Settings',
                'icon' => 'gear',
                'order' => 900,
            ],
        ],
    ],
];
