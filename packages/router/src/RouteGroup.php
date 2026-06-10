<?php

namespace Codemonster\Router;

class RouteGroup
{
    protected string $prefix;
    protected \Closure $callback;
    protected Router $router;
    /** @var list<list<string|array<mixed>>> */
    protected array $middleware = [];
    /** @var list<list<string|array<mixed>>> */
    protected array $parentMiddleware = [];

    /** @param list<list<string|array<mixed>>> $parentMiddleware */
    public function __construct(string $prefix, callable $callback, Router $router, array $parentMiddleware = [])
    {
        $this->prefix = rtrim($prefix, '/');
        $this->callback = \Closure::fromCallable($callback);
        $this->router = $router;
        $this->parentMiddleware = $parentMiddleware;
    }

    /** @param string|array<mixed> ...$middleware */
    public function middleware(string|array ...$middleware): static
    {
        $this->middleware[] = array_values($middleware);

        return $this;
    }

    /** @return list<list<string|array<mixed>>> */
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

    public function any(string $path, mixed $handler): Route
    {
        $route = $this->router->any($this->prefix . $path, $handler);

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
            array_merge($this->parentMiddleware, $this->middleware),
        );
    }
}
