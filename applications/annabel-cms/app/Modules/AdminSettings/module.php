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
    'admin' => [
        'navigation' => [
            [
                'id' => 'admin.settings.general',
                'parent' => 'settings',
                'label' => 'General',
                'href' => '/admin/settings/general',
                'order' => 50,
            ],
        ],
    ],
];
