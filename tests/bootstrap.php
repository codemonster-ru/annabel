<?php

require __DIR__ . '/../vendor/autoload.php';

use Codemonster\Annabel\Application;
use Codemonster\View\View;
use Codemonster\View\Locator\DefaultLocator;
use Codemonster\View\Engines\PhpEngine;

$app = new Application(__DIR__ . '/..', null, false);

$app->getContainer()->singleton(View::class, function () {
    $temp = sys_get_temp_dir();
    $locator = new DefaultLocator([$temp]);
    $engine = new PhpEngine($locator, ['php']);
    return new View(['php' => $engine], 'php');
});

$app->bootstrap();

return $app;
