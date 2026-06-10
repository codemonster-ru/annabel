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
    protected ?string $name = null;
    /** @var array<string, string> */
    protected array $parameters = [];
    /** @var array<string, string> */
    protected array $constraints = [];

    /** @param string|list<string> $methods */
    public function __construct(array|string $methods, string $path, mixed $handler)
    {
        $this->methods = array_values(array_map('strtoupper', (array) $methods));
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

    public function name(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    /** @return array<string, string> */
    public function getConstraints(): array
    {
        return $this->constraints;
    }

    /** @param array<string, string>|string $name */
    public function where(array|string $name, ?string $pattern = null): static
    {
        if (is_array($name)) {
            foreach ($name as $key => $value) {
                $this->constraints[$key] = $value;
            }

            return $this;
        }

        if ($pattern === null) {
            throw new \InvalidArgumentException('Route constraint pattern is required.');
        }

        $this->constraints[$name] = $pattern;

        return $this;
    }

    public function matches(string $method, string $uri): bool
    {
        $this->parameters = [];

        if (!in_array(strtoupper($method), $this->methods, true)) {
            return false;
        }

        $matches = [];
        if (preg_match($this->regex(), $uri, $matches) !== 1) {
            return false;
        }

        foreach ($this->parameterNames() as $name) {
            if (isset($matches[$name]) && is_string($matches[$name])) {
                $this->parameters[$name] = rawurldecode($matches[$name]);
            }
        }

        return true;
    }

    /** @return array<string, string> */
    public function parameters(): array
    {
        return $this->parameters;
    }

    /** @param array<string, scalar|null> $parameters */
    public function uri(array $parameters = []): string
    {
        $used = [];
        $uri = preg_replace_callback(
            '/\{([A-Za-z_][A-Za-z0-9_]*)\}/',
            function (array $matches) use ($parameters, &$used): string {
                $name = $matches[1];

                if (!array_key_exists($name, $parameters)) {
                    throw new \InvalidArgumentException("Missing route parameter [{$name}].");
                }

                $used[] = $name;

                return rawurlencode((string) $parameters[$name]);
            },
            $this->path,
        );

        if (!is_string($uri)) {
            throw new \RuntimeException("Unable to generate URI for route [{$this->path}].");
        }

        $query = array_diff_key($parameters, array_flip($used));
        if ($query !== []) {
            $uri .= '?' . http_build_query($query);
        }

        return $uri;
    }

    protected function regex(): string
    {
        $regex = '';
        $offset = 0;
        preg_match_all('/\{([A-Za-z_][A-Za-z0-9_]*)\}/', $this->path, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[0] as $index => $match) {
            [$placeholder, $position] = $match;
            $name = $matches[1][$index][0];
            $regex .= preg_quote(substr($this->path, $offset, $position - $offset), '#');
            $regex .= '(?P<' . $name . '>' . ($this->constraints[$name] ?? '[^/]+') . ')';
            $offset = $position + strlen($placeholder);
        }

        $regex .= preg_quote(substr($this->path, $offset), '#');

        return '#^' . $regex . '$#';
    }

    /** @return list<string> */
    protected function parameterNames(): array
    {
        preg_match_all('/\{([A-Za-z_][A-Za-z0-9_]*)\}/', $this->path, $matches);

        return $matches[1];
    }
}
