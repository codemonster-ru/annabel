<?php

$basePath = dirname(__DIR__);

$paths = [];

$modulesPattern = $basePath
    . DIRECTORY_SEPARATOR . 'app'
    . DIRECTORY_SEPARATOR . 'Modules'
    . DIRECTORY_SEPARATOR . '*'
    . DIRECTORY_SEPARATOR . 'database'
    . DIRECTORY_SEPARATOR . 'migrations';

foreach (glob($modulesPattern) ?: [] as $path) {
    $paths[] = $path;
}

return array_values(array_unique($paths));
