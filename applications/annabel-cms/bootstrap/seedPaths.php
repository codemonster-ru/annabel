<?php

$basePath = dirname(__DIR__);

$paths = [];

$paths[] = $basePath
    . DIRECTORY_SEPARATOR . 'database'
    . DIRECTORY_SEPARATOR . 'seeds';

$modulesPattern = $basePath
    . DIRECTORY_SEPARATOR . 'app'
    . DIRECTORY_SEPARATOR . 'Modules'
    . DIRECTORY_SEPARATOR . '*'
    . DIRECTORY_SEPARATOR . 'database'
    . DIRECTORY_SEPARATOR . 'seeds';

// `make:seed` always uses the first registered path.

foreach (glob($modulesPattern) ?: [] as $path) {
    $paths[] = $path;
}

return array_values(array_unique($paths));
