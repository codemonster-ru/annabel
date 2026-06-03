<?php

namespace Codemonster\Router;

class RouteCollection
{
    /** @var Route[] */
    protected array $routes = [];

    public function addRoute(Route $route): void
    {
        foreach ($this->routes as $existing) {
            if (
                $existing->path === $route->path &&
                array_intersect($existing->methods, $route->methods)
            ) {
                throw new \RuntimeException(
                    "Duplicate route detected: [" . implode('|', $route->methods) . " {$route->path}]"
                );
            }
        }

        $this->routes[] = $route;
    }

    public function match(string $method, string $uri): ?Route
    {
        foreach ($this->routes as $route) {
            if (in_array($method, $route->methods, true) && $route->path === $uri) {
                return $route;
            }
        }

        return null;
    }
}
