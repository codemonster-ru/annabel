<?php

use Codemonster\Annabel\Application;
use Codemonster\Xen\Modules\Core\ModuleServiceProvider;

require_once __DIR__ . '/../vendor/autoload.php';

$app = new Application(__DIR__ . '/..');

$core = new ModuleServiceProvider($app);
$core->register();
$core->boot();

return $app;
