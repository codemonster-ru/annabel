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
        return $this->routes[$method][$uri] ?? null;
    }
}
