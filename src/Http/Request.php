<?php

namespace Codemonster\Annabel\Http;

class Request
{
    protected string $method;
    protected string $uri;
    protected array $query = [];
    protected array $body = [];
    protected array $headers = [];
    protected string $rawBody = '';

    public function __construct(
        string $method,
        string $uri,
        array $query = [],
        array $body = [],
        array $headers = [],
        string $rawBody = ''
    ) {
        $this->method = strtoupper($method);
        $this->uri = parse_url($uri, PHP_URL_PATH) ?? '/';
        $this->query = $query;
        $this->body = $body;
        $this->headers = $headers;
        $this->rawBody = $rawBody;
    }

    /**
     * Захватывает текущий HTTP-запрос из глобальных переменных.
     */
    public static function capture(): static
    {
        $headers = [];

        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                $headers[$name] = $value;
            }
        }

        $rawBody = file_get_contents('php://input');
        $contentType = $headers['Content-Type'] ?? '';

        $body = $_POST;

        if (stripos($contentType, 'application/json') !== false) {
            $json = json_decode($rawBody, true);
            if (is_array($json)) {
                $body = $json;
            }
        }

        return new static(
            $_SERVER['REQUEST_METHOD'] ?? 'GET',
            $_SERVER['REQUEST_URI'] ?? '/',
            $_GET ?? [],
            $body,
            $headers,
            $rawBody
        );
    }

    // ============================================================
    // Basic accessors
    // ============================================================

    public function method(): string
    {
        return $this->method;
    }

    public function uri(): string
    {
        return $this->uri;
    }

    public function query(?string $key = null, mixed $default = null): mixed
    {
        return $key === null ? $this->query : ($this->query[$key] ?? $default);
    }

    public function input(?string $key = null, mixed $default = null): mixed
    {
        return $key === null ? $this->body : ($this->body[$key] ?? $default);
    }

    public function header(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->headers;
        }

        $key = strtolower($key);

        foreach ($this->headers as $name => $value) {
            if (strtolower($name) === $key) {
                return $value;
            }
        }

        return $default;
    }

    public function body(): string
    {
        return $this->rawBody;
    }

    // ============================================================
    // Helpers
    // ============================================================

    public function is(string $method): bool
    {
        return strtoupper($this->method) === strtoupper($method);
    }

    public function wantsJson(): bool
    {
        $accept = $this->header('Accept', '');

        return str_contains($accept, 'application/json');
    }

    public function scheme(): string
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    }

    public function host(): string
    {
        return $_SERVER['HTTP_HOST'] ?? 'localhost';
    }

    public function fullUrl(): string
    {
        return $this->scheme() . '://' . $this->host() . $this->uri;
    }
}
