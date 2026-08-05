<?php

return [
    'name' => 'AdminUsers',
    'version' => '1.0.0',
    'system' => true,
    'author' => [
        'name' => 'Codemonster',
        'url' => 'https://codemonster.net',
    ],
    'dependencies' => ['Admin', 'Auth'],
    'routes' => 'routes/web.php',
    'views' => null,
    'permissions' => [
        ['code' => 'users.view', 'name' => 'View users', 'category' => 'Users', 'sort_order' => 300],
        ['code' => 'users.create', 'name' => 'Create users', 'category' => 'Users', 'sort_order' => 310],
        ['code' => 'users.update', 'name' => 'Update users', 'category' => 'Users', 'sort_order' => 320],
        ['code' => 'users.delete', 'name' => 'Delete users', 'category' => 'Users', 'sort_order' => 330],
        ['code' => 'roles.view', 'name' => 'View roles', 'category' => 'Roles', 'sort_order' => 400],
        ['code' => 'roles.create', 'name' => 'Create roles', 'category' => 'Roles', 'sort_order' => 410],
        ['code' => 'roles.update', 'name' => 'Update roles and permissions', 'category' => 'Roles', 'sort_order' => 420],
        ['code' => 'roles.delete', 'name' => 'Delete roles', 'category' => 'Roles', 'sort_order' => 430],
    ],
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
                'permission' => 'users.view',
                'order' => 100,
            ],
            [
                'id' => 'admin.users.roles',
                'parent' => 'admin.users',
                'label' => 'Roles',
                'href' => '/admin/roles',
                'permission' => 'roles.view',
                'order' => 110,
            ],
        ],
    ],
];
