<?php

namespace Codemonster\Router;

class RouteGroup
{
    protected string $prefix;
    protected $callback;
    protected Router $router;
    protected array $middleware = [];
    protected array $parentMiddleware = [];

    public function __construct(string $prefix, callable $callback, Router $router, array $parentMiddleware = [])
    {
        $this->prefix = rtrim($prefix, '/');
        $this->callback = $callback;
        $this->router = $router;
        $this->parentMiddleware = $parentMiddleware;
    }

    public function middleware(string|array ...$middleware): static
    {
        $this->middleware[] = $middleware;

        return $this;
    }

    protected function fullMiddleware(): array
    {
        return array_merge($this->parentMiddleware, $this->middleware);
    }

    public function run(): void
    {
        ($this->callback)($this);
    }

    public function get(string $path, mixed $handler): Route
    {
        $route = $this->router->get($this->prefix . $path, $handler);

        foreach ($this->fullMiddleware() as $mw) {
            $route->middleware(...$mw);
        }

        return $route;
    }

    public function post(string $path, mixed $handler): Route
    {
        $route = $this->router->post($this->prefix . $path, $handler);

        foreach ($this->fullMiddleware() as $mw) {
            $route->middleware(...$mw);
        }

        return $route;
    }

    public function group(string $prefix, callable $callback): RouteGroup
    {
        return $this->router->group(
            $this->prefix . $prefix,
            $callback,
            array_merge($this->parentMiddleware, $this->middleware)
        );
    }
}
