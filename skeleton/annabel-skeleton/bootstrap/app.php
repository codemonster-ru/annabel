<?php

use Codemonster\Annabel\Application;

$baseDir = __DIR__ . '/..';

$app = new Application($baseDir);

$routeCache = "$baseDir/bootstrap/cache/routes.php";

if (PHP_SAPI !== 'cli' && is_file($routeCache)) {
    $app->loadCachedRoutes($routeCache);
} else {
    require "$baseDir/routes/web.php";
}

return $app;
