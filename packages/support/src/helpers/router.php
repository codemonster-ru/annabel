<?php

use Codemonster\Router\Router;
use Codemonster\Router\Route;

if (!function_exists('router')) {
    /** @param callable|array{mixed, mixed}|null $handler */
    function router(?string $path = null, callable|array|null $handler = null, string $method = 'GET'): Router|Route
    {
        $router = app('router');
        if (!$router instanceof Router) {
            throw new RuntimeException('Router service is not available.');
        }

        if ($path !== null && $handler !== null) {
            $route = $router->{$method}($path, $handler);
            if (!$route instanceof Route) {
                throw new RuntimeException('Router did not return a route.');
            }

            return $route;
        }

        return $router;
    }
}

if (!function_exists('route')) {
    /** @param callable|array{mixed, mixed} $handler */
    function route(string $path, callable|array $handler, string $method = 'GET'): Route
    {
        /** @var Route $route */
        $route = router($path, $handler, $method);

        return $route;
    }
}
