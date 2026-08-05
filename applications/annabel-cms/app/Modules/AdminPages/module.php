<?php

use Codemonster\Cms\Modules\AdminPages\ModuleServiceProvider;

return [
    'name' => 'AdminPages',
    'version' => '1.0.0',
    'system' => true,
    'author' => [
        'name' => 'Codemonster',
        'url' => 'https://codemonster.net',
    ],
    'dependencies' => ['Admin', 'Pages'],
    'provider' => ModuleServiceProvider::class,
    'routes' => 'routes/web.php',
    'views' => null,
    'migrations' => 'database/migrations',
    'permissions' => [
        ['code' => 'pages.view', 'name' => 'View pages', 'category' => 'Pages', 'sort_order' => 200],
        ['code' => 'pages.create', 'name' => 'Create pages', 'category' => 'Pages', 'sort_order' => 210],
        ['code' => 'pages.update', 'name' => 'Update any page', 'category' => 'Pages', 'sort_order' => 220],
        ['code' => 'pages.update.own', 'name' => 'Update owned pages', 'category' => 'Pages', 'sort_order' => 225],
        ['code' => 'pages.delete', 'name' => 'Delete any page', 'category' => 'Pages', 'sort_order' => 230],
        ['code' => 'pages.delete.own', 'name' => 'Delete owned pages', 'category' => 'Pages', 'sort_order' => 235],
        ['code' => 'pages.publish', 'name' => 'Publish any page', 'category' => 'Pages', 'sort_order' => 240],
        ['code' => 'pages.publish.own', 'name' => 'Publish owned pages', 'category' => 'Pages', 'sort_order' => 245],
        ['code' => 'pages.assign_owner', 'name' => 'Transfer page ownership', 'category' => 'Pages', 'sort_order' => 250],
    ],
    'admin' => [
        'navigation' => [
            [
                'id' => 'admin.pages',
                'label' => 'Pages',
                'icon' => 'file-text',
                'href' => '/admin/pages',
                'permission' => 'pages.view',
                'order' => 300,
            ],
        ],
    ],
];
