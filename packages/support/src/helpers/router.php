<?php

use Codemonster\Router\Router;
use Codemonster\Router\Route;

if (!function_exists('router')) {
    function router(?string $path = null, callable|array|null $handler = null, string $method = 'GET'): Router|Route
    {
        $router = app('router');

        if ($path !== null && $handler !== null) {
            return $router->{$method}($path, $handler);
        }

        return $router;
    }
}

if (!function_exists('route')) {
    function route(string $path, callable|array $handler, string $method = 'GET'): Route
    {
        return router($path, $handler, $method);
    }
}
