<?php

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/SupportFakeContainer.php';

if (!function_exists('app')) {
    function app(?string $abstract = null)
    {
        static $container;

        if ($container === null) {
            $container = new \Codemonster\Support\Tests\SupportFakeContainer();
        }

        return $abstract ? $container->make($abstract) : $container;
    }
}
