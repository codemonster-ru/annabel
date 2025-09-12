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
        if (array_key_exists($method, $this->routes) && array_key_exists($uri, $this->routes[$method])) {
            return $this->routes[$method][$uri];
        }

        return null;
    }
}
