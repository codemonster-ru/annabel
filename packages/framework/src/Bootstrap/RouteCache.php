<?php

namespace Codemonster\Annabel\Bootstrap;

use Codemonster\Router\Route;
use Codemonster\Router\Router;

final class RouteCache
{
    public static function path(string $basePath): string
    {
        return $basePath . '/bootstrap/cache/routes.php';
    }

    public static function write(string $basePath, Router $router): int
    {
        $routes = array_map(self::definition(...), $router->routes());
        CacheFile::write(self::path($basePath), $routes);

        return count($routes);
    }

    public static function load(string $path, Router $router): void
    {
        $definitions = require $path;

        if (!is_array($definitions)) {
            throw new \RuntimeException("Route cache must return an array: {$path}");
        }

        foreach ($definitions as $definition) {
            if (!is_array($definition)) {
                throw new \RuntimeException("Route cache contains an invalid definition: {$path}");
            }

            self::register($router, $definition);
        }
    }

    public static function clear(string $basePath): bool
    {
        return CacheFile::clear(self::path($basePath));
    }

    /**
     * @return array{
     *   methods: list<string>,
     *   path: string,
     *   handler: string|array{string, string},
     *   middleware: list<list<string|array<mixed>>>,
     *   name: string|null,
     *   constraints: array<string, string>
     * }
     */
    private static function definition(Route $route): array
    {
        $handler = $route->handler;
        if (is_array($handler)
            && count($handler) === 2
            && is_string($handler[0] ?? null)
            && is_string($handler[1] ?? null)) {
            $handler = [$handler[0], $handler[1]];
        } elseif (!is_string($handler)) {
            throw new \RuntimeException(
                'Route [' . implode('|', $route->methods) . " {$route->path}] cannot be cached because its handler is not a class method or string.",
            );
        }

        /** @var string|array{string, string} $handler */
        return [
            'methods' => $route->methods,
            'path' => $route->path,
            'handler' => $handler,
            'middleware' => $route->getMiddleware(),
            'name' => $route->getName(),
            'constraints' => $route->getConstraints(),
        ];
    }

    /**
     * @param array<mixed> $definition
     */
    private static function register(Router $router, array $definition): void
    {
        $methods = $definition['methods'] ?? null;
        $path = $definition['path'] ?? null;
        $handler = $definition['handler'] ?? null;
        $middleware = $definition['middleware'] ?? [];
        $name = $definition['name'] ?? null;
        $constraints = $definition['constraints'] ?? [];

        if (!is_array($methods)
            || !is_string($path)
            || (!is_string($handler) && !is_array($handler))
            || (!is_string($name) && $name !== null)
            || !is_array($middleware)
            || !is_array($constraints)) {
            throw new \RuntimeException('Route cache contains invalid route metadata.');
        }

        $normalizedMethods = [];
        foreach ($methods as $method) {
            if (!is_string($method) || $method === '') {
                throw new \RuntimeException('Route cache contains invalid route methods.');
            }
            $normalizedMethods[] = $method;
        }
        if ($normalizedMethods === []) {
            throw new \RuntimeException('Route cache contains invalid route methods.');
        }

        if (is_array($handler)) {
            if (count($handler) !== 2
                || !is_string($handler[0] ?? null)
                || !is_string($handler[1] ?? null)) {
                throw new \RuntimeException('Route cache contains an invalid route handler.');
            }
            $handler = [$handler[0], $handler[1]];
        }

        $route = $router->add($normalizedMethods, $path, $handler);

        if (is_string($name)) {
            $route->name($name);
        }

        $normalizedConstraints = [];
        foreach ($constraints as $key => $value) {
            if (!is_string($key) || !is_string($value)) {
                throw new \RuntimeException('Route cache contains invalid route constraints.');
            }
            $normalizedConstraints[$key] = $value;
        }
        if ($normalizedConstraints !== []) {
            $route->where($normalizedConstraints);
        }

        foreach ($middleware as $entry) {
            if (!is_array($entry)) {
                throw new \RuntimeException('Route cache contains invalid middleware metadata.');
            }

            $normalizedMiddleware = [];
            foreach ($entry as $value) {
                if (!is_string($value) && !is_array($value)) {
                    throw new \RuntimeException('Route cache contains invalid middleware metadata.');
                }
                $normalizedMiddleware[] = $value;
            }

            $route->middleware(...$normalizedMiddleware);
        }
    }
}
