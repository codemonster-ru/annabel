<?php

return [
    'name' => 'Pages',
    'version' => '1.0.0',
    'author' => [
        'name' => 'Codemonster',
        'url' => 'https://codemonster.net',
    ],
    'dependencies' => ['Core', 'Settings'],
    'routes' => 'routes/web.php',
    'views' => 'views',
    'migrations' => 'database/migrations',
    'seeds' => 'database/seeds',
];
