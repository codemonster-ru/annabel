<?php

namespace Annabel\Http;

class Request
{
    protected string $method;
    protected string $uri;

    public function __construct(string $method, string $uri)
    {
        $this->method = $method;
        $this->uri = parse_url($uri, PHP_URL_PATH) ?? '/';
    }

    public static function capture(): static
    {
        return new static($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
    }

    public function method(): string
    {
        return $this->method;
    }

    public function uri(): string
    {
        return $this->uri;
    }
}
