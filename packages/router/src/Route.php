<?php

namespace Codemonster\Router;

class Route
{
    public array $methods;
    public string $path;
    public mixed $handler;
    protected array $middleware = [];

    public function __construct(array|string $methods, string $path, mixed $handler)
    {
        $this->methods = (array)$methods;
        $this->path = $path;
        $this->handler = $handler;
    }

    public function middleware(string|array ...$middleware): static
    {
        $this->middleware[] = $middleware;

        return $this;
    }

    public function getMiddleware(): array
    {
        return $this->middleware;
    }
}
