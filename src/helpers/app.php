<?php

use Codemonster\Annabel\Application;

if (!function_exists('app')) {
    function app(?string $abstract = null, array $parameters = []): mixed
    {
        if (!class_exists(Application::class) || !Application::isInitialized()) {
            throw new RuntimeException('Application is not initialized.');
        }

        $app = Application::getInstance();

        if ($abstract === null) {
            return $app;
        }

        return $app->getContainer()->make($abstract, $parameters);
    }
}
