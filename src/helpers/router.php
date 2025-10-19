<?php

use Codemonster\Router\Router;
use Codemonster\Router\Route;

if (!function_exists('router')) {
    function router(?string $path = null, callable|array|null $handler = null, string $method = 'GET'): Router|Route
    {
        $app = function_exists('app') ? app() : null;
        $router = null;

        if ($app && $app->has(Router::class)) {
            $router = $app->make(Router::class);
        } elseif ($app && method_exists($app, 'getKernel')) {
            $kernel = $app->getKernel();

            if (method_exists($kernel, 'getRouter')) {
                $router = $kernel->getRouter();
            }
        }

        if (!$router instanceof Router) {
            $router = new Router();

            if ($app) {
                $app->singleton(Router::class, fn() => $router);
            }
        }

        if ($path !== null && $handler !== null) {
            return $router->{$method}($path, $handler);
        }

        return $router;
    }
}

if (!function_exists('route')) {
    function route(string $path, callable|array $handler, string $method = 'GET'): \Codemonster\Router\Route
    {
        return router($path, $handler, $method);
    }
}
