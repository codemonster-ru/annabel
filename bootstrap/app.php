<?php

use Codemonster\Annabel\Application;
use Codemonster\Xen\Modules\Core\ModuleServiceProvider as XenCoreProvider;

require_once __DIR__ . '/../vendor/autoload.php';

$app = new Application(__DIR__ . '/../');

(new XenCoreProvider($app))->register();
(new XenCoreProvider($app))->boot();

return $app;
