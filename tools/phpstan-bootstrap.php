<?php

declare(strict_types=1);

$root = dirname(__DIR__);

require_once $root . '/vendor/autoload.php';

foreach (glob($root . '/packages/*/vendor/autoload.php') ?: [] as $autoload) {
    require_once $autoload;
}

spl_autoload_register(static function (string $class) use ($root): void {
    $providerPrefix = 'App\\Providers\\';

    if (str_starts_with($class, $providerPrefix)) {
        $file = $root . '/skeleton/annabel-skeleton/bootstrap/providers/'
            . str_replace('\\', '/', substr($class, strlen($providerPrefix))) . '.php';

        if (is_file($file)) {
            require_once $file;
        }

        return;
    }

    $prefix = 'App\\';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $file = $root . '/skeleton/annabel-skeleton/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';

    if (is_file($file)) {
        require_once $file;
    }
});
