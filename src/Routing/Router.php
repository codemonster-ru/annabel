<?php

namespace Annabel\Routing;

class Router
{
    protected array $routes = [];

    public function add(string $method, string $path, callable|array $handler): void
    {
        $this->routes[$method][$path] = $handler;
    }

    public function match(string $method, string $uri): callable|array|null
    {
        $path = strtok($uri, '?');

        return $this->routes[$method][$path] ?? null;
    }
}
