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
        ],
    ],
];
