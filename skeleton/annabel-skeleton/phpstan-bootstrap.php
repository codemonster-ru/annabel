<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$skeleton = __DIR__;

foreach (glob($root . '/packages/*/vendor/autoload.php') ?: [] as $autoload) {
    require_once $autoload;
}

foreach (glob($root . '/packages/*/src/helpers/*.php') ?: [] as $helper) {
    require_once $helper;
}

spl_autoload_register(static function (string $class) use ($skeleton): void {
    $prefix = 'App\\';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $file = $skeleton . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';

    if (is_file($file)) {
        require_once $file;
    }
});
