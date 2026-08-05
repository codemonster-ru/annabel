<?php

return [
    'name' => 'AdminSettings',
    'version' => '1.0.0',
    'system' => true,
    'author' => [
        'name' => 'Codemonster',
        'url' => 'https://codemonster.net',
    ],
    'dependencies' => ['Admin', 'Settings'],
    'routes' => 'routes/web.php',
    'views' => null,
    'permissions' => [
        ['code' => 'settings.view', 'name' => 'View site settings', 'category' => 'Settings', 'sort_order' => 500],
        ['code' => 'settings.update', 'name' => 'Update site settings', 'category' => 'Settings', 'sort_order' => 510],
    ],
    'admin' => [
        'navigation' => [
            [
                'id' => 'admin.settings.general',
                'parent' => 'settings',
                'label' => 'General',
                'href' => '/admin/settings/general',
                'permission' => 'settings.view',
                'order' => 50,
            ],
        ],
    ],
];
