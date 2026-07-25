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
                'label' => 'Users',
                'icon' => 'users',
                'order' => 200,
            ],
            [
                'id' => 'admin.users.list',
                'parent' => 'admin.users',
                'label' => 'User list',
                'href' => '/admin/users',
                'order' => 100,
            ],
        ],
    ],
];
