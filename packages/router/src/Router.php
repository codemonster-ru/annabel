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
        $route = new Route('GET', $path, $handler);

        $this->routes->addRoute($route);

        return $route;
    }

    public function post(string $path, mixed $handler): Route
    {
        $route = new Route('POST', $path, $handler);

        $this->routes->addRoute($route);

        return $route;
    }

    public function any(string $path, mixed $handler): Route
    {
        $methods = ['GET', 'POST'];
        $route = new Route($methods, $path, $handler);

        $this->routes->addRoute($route);

        return $route;
    }

    public function dispatch(string $method, string $uri): ?Route
    {
        $uri = parse_url($uri, PHP_URL_PATH);
        $uri = rtrim($uri, '/');
        $uri = $uri === '' ? '/' : $uri;

        $route = $this->routes->match($method, $uri);

        if (!$route && $uri !== '/') {
            $alt = $uri . '/';
            $route = $this->routes->match($method, $alt);
        }

        return $route;
    }

    public function group(string $prefix, callable $callback, array $parentMiddleware = []): RouteGroup
    {
        $group = new RouteGroup($prefix, $callback, $this, $parentMiddleware);

        $group->run();

        return $group;
    }
}
