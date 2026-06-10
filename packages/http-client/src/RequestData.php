<?php

namespace Codemonster\HttpClient;

class RequestData
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        protected string $method,
        protected string $url,
        protected array $headers = [],
        protected ?string $body = null,
        protected float $timeout = 30.0,
    ) {
    }

    public function method(): string
    {
        return $this->method;
    }

    public function url(): string
    {
        return $this->url;
    }

    /**
     * @return array<string, string>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    public function body(): ?string
    {
        return $this->body;
    }

    public function timeout(): float
    {
        return $this->timeout;
    }
}
