<?php

use Codemonster\Cms\Modules\Settings\ModuleServiceProvider;

return [
    'name' => 'Settings',
    'version' => '1.0.0',
    'dependencies' => ['Core'],
    'provider' => ModuleServiceProvider::class,
    'routes' => null,
    'views' => null,
    'migrations' => 'database/migrations',
    'seeds' => 'database/seeds',
];
