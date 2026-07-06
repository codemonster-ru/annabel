<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

if (!function_exists('env')) {
    function env(string $key, bool|float|int|string|null $default = null): bool|float|int|string|null
    {
        return $default;
    }
}
