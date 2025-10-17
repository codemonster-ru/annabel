<?php

namespace Codemonster\Annabel\Http;

class Response
{
    protected string $content = '';
    protected int $status = 200;
    protected array $headers = [];

    public function __construct(string $content = '', int $status = 200, array $headers = [])
    {
        $this->setContent($content);
        $this->status = $status;
        $this->headers = $headers;
    }

    // ============================================================
    // Core accessors
    // ============================================================

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(mixed $content): static
    {
        $this->content = is_string($content)
            ? $content
            : (is_scalar($content)
                ? (string) $content
                : json_encode($content, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        return $this;
    }

    public function getStatusCode(): int
    {
        return $this->status;
    }

    public function setStatusCode(int $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    // ============================================================
    // Header management
    // ============================================================

    public function header(string $name, string $value): static
    {
        $this->headers[$name] = $value;

        return $this;
    }

    public function setHeaders(array $headers): static
    {
        foreach ($headers as $name => $value) {
            $this->headers[$name] = $value;
        }

        return $this;
    }

    public function hasHeader(string $name): bool
    {
        return isset($this->headers[$name]);
    }

    // ============================================================
    // Helpers
    // ============================================================

    public static function json(mixed $data, int $status = 200, array $headers = []): static
    {
        $headers = array_merge(['Content-Type' => 'application/json; charset=utf-8'], $headers);

        $content = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        if ($content === false) {
            $content = json_encode(['error' => 'Failed to encode JSON'], JSON_UNESCAPED_UNICODE);
            $status = 500;
        }

        return new static($content, $status, $headers);
    }

    public static function redirect(string $url, int $status = 302, array $headers = []): static
    {
        $headers = array_merge(['Location' => $url], $headers);

        return new static('', $status, $headers);
    }

    public function type(string $mime): static
    {
        $this->headers['Content-Type'] = $mime;

        return $this;
    }

    public function isJson(): bool
    {
        return isset($this->headers['Content-Type'])
            && str_contains(strtolower($this->headers['Content-Type']), 'application/json');
    }

    // ============================================================
    // Output
    // ============================================================

    public function send(): void
    {
        http_response_code($this->status);

        foreach ($this->headers as $name => $value) {
            header("$name: $value", true);
        }

        echo $this->content;
    }

    // ============================================================
    // Sugar syntax
    // ============================================================

    public function __toString(): string
    {
        return $this->content;
    }
}
