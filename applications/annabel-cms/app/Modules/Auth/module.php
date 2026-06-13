<?php

use Codemonster\Cms\Modules\Auth\ModuleServiceProvider;

return [
    'name' => 'Auth',
    'version' => '1.0.0',
    'dependencies' => ['Core'],
    'provider' => ModuleServiceProvider::class,
    'routes' => 'routes/web.php',
    'views' => 'views',
];
