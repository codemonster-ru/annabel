<?php

use Codemonster\Cms\Modules\Setup\ModuleServiceProvider;

return [
    'name' => 'Setup',
    'version' => '1.0.0',
    'dependencies' => ['Core'],
    'provider' => ModuleServiceProvider::class,
    'routes' => 'routes/web.php',
    'views' => 'views',
    'assets' => [
        'vite_config' => 'vite.config.js',
        'manifest' => 'public/setup/assets/.vite/manifest.json',
        'public_path' => '/setup/assets',
    ],
];
