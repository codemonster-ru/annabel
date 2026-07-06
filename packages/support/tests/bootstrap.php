<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/SupportFakeContainer.php';

if (!function_exists('app')) {
    /**
     * @return ($abstract is null ? \Codemonster\Support\Tests\SupportFakeContainer : mixed)
     */
    function app(?string $abstract = null): mixed
    {
        /** @var \Codemonster\Support\Tests\SupportFakeContainer|null $container */
        static $container;

        if ($container === null) {
            $container = new \Codemonster\Support\Tests\SupportFakeContainer();
        }

        return $abstract ? $container->make($abstract) : $container;
    }
}
