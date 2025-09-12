<?php

namespace Annabel\Routing;

class Router
{
    protected array $routes = [];

    public function add(string $method, string $uri, callable $action): void
    {
        $this->routes[$method][$uri] = $action;
    }

    public function match(string $method, string $uri): ?callable
    {
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $route['uri'] === $uri) {
                return $route['action'];
            }
        }
        return null;
    }
}
