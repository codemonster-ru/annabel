<?php

use Codemonster\Annabel\Application;
use Codemonster\Cms\Modules\Core\ModuleServiceProvider;

require_once __DIR__ . '/../vendor/autoload.php';

$app = new Application(__DIR__ . '/..');
$app->getContainer()->instance(Application::class, $app);

$core = new ModuleServiceProvider($app);
$core->register();
$core->boot();

return $app;
