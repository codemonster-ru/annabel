<?php

return [
    'name' => 'AdminUsers',
    'version' => '1.0.0',
    'dependencies' => ['Admin', 'Auth'],
    'routes' => 'routes/web.php',
    'views' => null,
    'admin' => [
        'navigation' => [
            [
                'id' => 'admin.users',
                'parent' => 'settings',
                'label' => 'Users',
                'order' => 100,
            ],
            [
                'id' => 'admin.users.list',
                'parent' => 'admin.users',
                'label' => 'User list',
                'href' => '/admin/settings/users',
                'order' => 100,
            ],
        ],
    ],
];
