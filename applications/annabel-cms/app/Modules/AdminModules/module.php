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
    'admin' => [
        'navigation' => [
            [
                'id' => 'admin.settings.modules',
                'parent' => 'settings',
                'label' => 'Modules',
                'href' => '/admin/settings/modules',
                'order' => 100,
            ],
            [
                'id' => 'admin.settings.system-updates',
                'parent' => 'settings',
                'label' => 'System updates',
                'href' => '/admin/settings/system/updates',
                'order' => 110,
            ],
        ],
    ],
];
