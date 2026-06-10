<?php

namespace Codemonster\HttpClient;

class HttpResponse
{
    /**
     * @param array<string, list<string>> $headers
     */
    public function __construct(
        protected int $status,
        protected string $body = '',
        protected array $headers = [],
    ) {
    }

    public function status(): int
    {
        return $this->status;
    }

    public function body(): string
    {
        return $this->body;
    }

    /**
     * @return array<string, list<string>>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    public function header(string $name): ?string
    {
        $values = $this->headers[strtolower($name)] ?? null;

        return $values !== null ? implode(', ', $values) : null;
    }

    public function successful(): bool
    {
        return $this->status >= 200 && $this->status < 300;
    }

    public function failed(): bool
    {
        return $this->status >= 400;
    }

    /**
     * @return array<mixed>|null
     */
    public function json(): ?array
    {
        if ($this->body === '') {
            return null;
        }

        $decoded = json_decode($this->body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new HttpClientException('Invalid JSON response: ' . json_last_error_msg());
        }

        return is_array($decoded) ? $decoded : null;
    }

    public function throw(): self
    {
        if ($this->failed()) {
            throw new HttpClientException("HTTP request failed with status [{$this->status}].");
        }

        return $this;
    }
}
