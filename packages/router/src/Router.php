<?php

namespace Codemonster\Router;

class Router
{
    protected RouteCollection $routes;

    public function __construct()
    {
        $this->routes = new RouteCollection();
    }

    public function get(string $path, mixed $handler): Route
    {
        return $this->add('GET', $path, $handler);
    }

    public function post(string $path, mixed $handler): Route
    {
        return $this->add('POST', $path, $handler);
    }

    public function any(string $path, mixed $handler): Route
    {
        return $this->add(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD'], $path, $handler);
    }

    /** @param string|list<string> $methods */
    public function add(string|array $methods, string $path, mixed $handler): Route
    {
        $route = new Route($methods, $path, $handler);
        $this->routes->addRoute($route);

        return $route;
    }

    /** @return list<Route> */
    public function routes(): array
    {
        return $this->routes->all();
    }

    /** @param array<string, scalar|null> $parameters */
    public function route(string $name, array $parameters = []): string
    {
        return $this->routes->route($name, $parameters);
    }

    public function dispatch(string $method, string $uri): ?Route
    {
        $uri = parse_url($uri, PHP_URL_PATH);
        $uri = is_string($uri) ? $uri : '/';
        $uri = rtrim($uri, '/');
        $uri = $uri === '' ? '/' : $uri;

        $route = $this->routes->match($method, $uri);

        if (!$route && $uri !== '/') {
            $alt = $uri . '/';
            $route = $this->routes->match($method, $alt);
        }

        return $route;
    }

    /** @param list<list<string|array<mixed>>> $parentMiddleware */
    public function group(string $prefix, callable $callback, array $parentMiddleware = []): RouteGroup
    {
        $group = new RouteGroup($prefix, $callback, $this, $parentMiddleware);

        $group->run();

        return $group;
    }
}
