<?php

namespace Codemonster\Router;

class RouteCollection
{
    /** @var list<Route> */
    protected array $routes = [];

    public function addRoute(Route $route): void
    {
        foreach ($this->routes as $existing) {
            if (
                $existing->path === $route->path &&
                array_intersect($existing->methods, $route->methods)
            ) {
                throw new \RuntimeException(
                    'Duplicate route detected: [' . implode('|', $route->methods) . " {$route->path}]",
                );
            }
        }

        $this->routes[] = $route;
    }

    /** @return list<Route> */
    public function all(): array
    {
        return $this->routes;
    }

    public function match(string $method, string $uri): ?Route
    {
        foreach ($this->routes as $route) {
            if ($route->matches($method, $uri)) {
                return $route;
            }
        }

        return null;
    }

    /** @param array<string, scalar|null> $parameters */
    public function route(string $name, array $parameters = []): string
    {
        foreach ($this->routes as $route) {
            if ($route->getName() === $name) {
                return $route->uri($parameters);
            }
        }

        throw new \RuntimeException("Route [{$name}] is not defined.");
    }
}
