<?php

return [
    'name' => 'AdminModules',
    'version' => '1.1.0',
    'system' => true,
    'author' => [
        'name' => 'Codemonster',
        'url' => 'https://codemonster.net',
    ],
    'dependencies' => ['Admin'],
    'routes' => 'routes/web.php',
    'views' => null,
    'permissions' => [
        ['code' => 'modules.view', 'name' => 'View modules', 'category' => 'Modules', 'sort_order' => 600],
        ['code' => 'modules.manage', 'name' => 'Install, update, enable, and disable modules', 'category' => 'Modules', 'sort_order' => 610],
        ['code' => 'system_updates.view', 'name' => 'View system updates', 'category' => 'System', 'sort_order' => 700],
    ],
    'admin' => [
        'navigation' => [
            [
                'id' => 'admin.settings.modules',
                'parent' => 'settings',
                'label' => 'Modules',
                'href' => '/admin/settings/modules',
                'permission' => 'modules.view',
                'order' => 100,
            ],
            [
                'id' => 'admin.settings.system-updates',
                'parent' => 'settings',
                'label' => 'System updates',
                'href' => '/admin/settings/system/updates',
                'permission' => 'system_updates.view',
                'order' => 110,
            ],
        ],
    ],
];
