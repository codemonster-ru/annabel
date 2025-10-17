<?php

use Codemonster\Router\Router;

if (!function_exists('router')) {
    function router(): Router
    {
        if (function_exists('app') && app()->has(Router::class)) {
            return app(Router::class);
        }

        if (function_exists('app') && method_exists(app(), 'getKernel')) {
            $kernel = app()->getKernel();

            if (method_exists($kernel, 'getRouter')) {
                $router = $kernel->getRouter();

                if ($router instanceof Router) {
                    return $router;
                }
            }
        }

        throw new RuntimeException(
            'Router instance not available in the current application context.'
        );
    }
}

if (!function_exists('route')) {
    function route(string $path, callable|array $handler, string $method = 'GET'): void
    {
        router()->{$method}($path, $handler);
    }
}
