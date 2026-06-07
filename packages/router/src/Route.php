<?php

namespace Codemonster\Router;

class Route
{
    /** @var list<string> */
    public array $methods;
    public string $path;
    public mixed $handler;
    /** @var list<list<string|array<mixed>>> */
    protected array $middleware = [];

    /** @param string|list<string> $methods */
    public function __construct(array|string $methods, string $path, mixed $handler)
    {
        $this->methods = (array)$methods;
        $this->path = $path;
        $this->handler = $handler;
    }

    /** @param string|array<mixed> ...$middleware */
    public function middleware(string|array ...$middleware): static
    {
        $this->middleware[] = array_values($middleware);

        return $this;
    }

    /** @return list<list<string|array<mixed>>> */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }
}
