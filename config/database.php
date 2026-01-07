<?php

$pathsFile = __DIR__ . '/../bootstrap/migrationPaths.php';
$defaultMigrationPaths = is_file($pathsFile) ? require $pathsFile : [];
$seedPathsFile = __DIR__ . '/../bootstrap/seedPaths.php';
$defaultSeedPaths = is_file($seedPathsFile) ? require $seedPathsFile : [];

return [
    'default' => 'mysql',

    'connections' => [
        'mysql' => [
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => (int) env('DB_PORT', 3306),
            'database' => env('DB_DATABASE', ''),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
        ],
    ],

    'migrations' => [
        'paths' => $defaultMigrationPaths,
        'table' => 'migrations',
    ],
    'seeds' => [
        'paths' => $defaultSeedPaths,
    ],
];
