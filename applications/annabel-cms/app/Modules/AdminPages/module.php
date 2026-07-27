<?php

return [
    'name' => 'AdminPages',
    'version' => '1.0.0',
    'dependencies' => ['Admin', 'Pages'],
    'routes' => 'routes/web.php',
    'views' => null,
    'migrations' => 'database/migrations',
    'admin' => [
        'navigation' => [
            [
                'id' => 'admin.pages',
                'label' => 'Pages',
                'icon' => 'file-text',
                'href' => '/admin/pages',
                'order' => 300,
            ],
        ],
    ],
];
