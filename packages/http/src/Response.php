<?php

namespace Codemonster\Http;

class Response
{
    protected string $content = '';
    protected int $status = 200;
    protected array $headers = [];
    protected array $cookies = [];

    private static function encodeJson(mixed $data, int $options): string
    {
        $json = json_encode($data, $options);

        if ($json === false) {
            throw new \RuntimeException('Failed to encode JSON: ' . json_last_error_msg());
        }

        return $json;
    }

    private function findHeaderValue(string $name): ?string
    {
        foreach ($this->headers as $key => $value) {
            if (strcasecmp($key, $name) === 0) {
                return $value;
            }
        }

        return null;
    }

    private function setHeaderValue(string $name, string|array $value): void
    {
        foreach ($this->headers as $key => $_) {
            if (strcasecmp($key, $name) === 0) {
                unset($this->headers[$key]);

                break;
            }
        }

        $this->headers[$name] = $value;
    }

    private static function buildCookieString(string $name, string $value, array $options): string
    {
        $cookie = $name . '=' . $value;
        $sameSite = null;

        if (isset($options['expires'])) {
            $expires = $options['expires'];

            if ($expires instanceof \DateTimeInterface) {
                $expires = $expires->getTimestamp();
            }

            if (is_int($expires)) {
                $cookie .= '; Expires=' . gmdate('D, d-M-Y H:i:s T', $expires);
            }
        }

        if (isset($options['max_age'])) {
            $cookie .= '; Max-Age=' . (int) $options['max_age'];
        }

        if (isset($options['path'])) {
            $cookie .= '; Path=' . $options['path'];
        }

        if (isset($options['domain'])) {
            $cookie .= '; Domain=' . $options['domain'];
        }

        if (!empty($options['secure'])) {
            $cookie .= '; Secure';
        }

        if (!empty($options['httponly'])) {
            $cookie .= '; HttpOnly';
        }

        if (isset($options['samesite'])) {
            $sameSite = ucfirst(strtolower((string) $options['samesite']));
            $allowed = ['Lax', 'Strict', 'None'];

            if (in_array($sameSite, $allowed, true)) {
                $cookie .= '; SameSite=' . $sameSite;
            }
        }

        if ($sameSite === 'None' && empty($options['secure'])) {
            $cookie .= '; Secure';
        }

        return $cookie;
    }

    protected function isCli(): bool
    {
        return PHP_SAPI === 'cli';
    }

    protected function headersSent(?string &$file = null, ?int &$line = null): bool
    {
        return headers_sent($file, $line);
    }

    protected function sendHeader(string $name, string $value, bool $replace): void
    {
        header("$name: $value", $replace);
    }

    private function sendInternal(bool $isHead): void
    {
        $noBodyStatus = in_array($this->status, [204, 304], true);

        if (!$this->isCli()) {
            if ($this->headersSent($file, $line)) {
                throw new \RuntimeException("Cannot send response, headers already sent in $file:$line");
            }

            http_response_code($this->status);

            foreach ($this->headers as $name => $value) {
                if ($noBodyStatus && strcasecmp($name, 'Content-Type') === 0) {
                    continue;
                }

                $replace = strcasecmp($name, 'Set-Cookie') !== 0;

                if (is_array($value)) {
                    foreach ($value as $item) {
                        $this->sendHeader($name, $item, false);
                    }
                } else {
                    $this->sendHeader($name, $value, $replace);
                }
            }

            foreach ($this->cookies as $cookie) {
                $this->sendHeader('Set-Cookie', $cookie, false);
            }
        }

        if (!$noBodyStatus && !$isHead) {
            echo $this->content;
        }
    }

    public function __construct(string $content = '', int $status = 200, array $headers = [])
    {
        $this->setContent($content);
        $this->status = $status;
        $this->setHeaders($headers);
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(mixed $content): static
    {
        if (is_string($content)) {
            $this->content = $content;
        } elseif (is_scalar($content)) {
            $this->content = (string) $content;
        } else {
            $this->content = self::encodeJson($content, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }

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

    public function withStatus(int $status): static
    {
        $clone = clone $this;
        $clone->status = $status;

        return $clone;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function header(string $name, string|array $value): static
    {
        $this->setHeaderValue($name, $value);

        return $this;
    }

    public function withHeader(string $name, string|array $value): static
    {
        $clone = clone $this;
        $clone->setHeaderValue($name, $value);

        return $clone;
    }

    public function withoutHeader(string $name): static
    {
        $clone = clone $this;

        foreach ($clone->headers as $key => $_) {
            if (strcasecmp($key, $name) === 0) {
                unset($clone->headers[$key]);
            }
        }

        return $clone;
    }

    public function withCookie(string $name, string $value, array $options = []): static
    {
        $clone = clone $this;
        $clone->cookies[$name] = self::buildCookieString($name, $value, $options);

        return $clone;
    }

    public function withoutCookie(string $name): static
    {
        $clone = clone $this;

        unset($clone->cookies[$name]);

        return $clone;
    }

    public function cookie(string $name, string $value, array $options = []): static
    {
        $this->cookies[$name] = self::buildCookieString($name, $value, $options);

        return $this;
    }

    public function setHeaders(array $headers): static
    {
        foreach ($headers as $name => $value) {
            $this->setHeaderValue($name, $value);
        }

        return $this;
    }

    public function withHeaders(array $headers): static
    {
        $clone = clone $this;

        return $clone->setHeaders($headers);
    }

    public function withoutHeaders(): static
    {
        $clone = clone $this;
        $clone->headers = [];

        return $clone;
    }

    public function hasHeader(string $name): bool
    {
        return $this->findHeaderValue($name) !== null;
    }

    public static function json(mixed $data, int $status = 200, array $headers = [], int $options = 0): static
    {
        $headers = array_merge(['Content-Type' => 'application/json; charset=utf-8'], $headers);
        $options |= JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT;

        try {
            $content = self::encodeJson($data, $options);
        } catch (\RuntimeException $e) {
            $content = self::encodeJson(['error' => 'Failed to encode JSON'], JSON_UNESCAPED_UNICODE);
            $status = 500;
        }

        return new static($content, $status, $headers);
    }

    public static function redirect(string $url, int $status = 302, array $headers = []): static
    {
        $headers = array_merge(['Location' => $url], $headers);

        return new static('', $status, $headers);
    }

    public static function empty(int $status = 204, array $headers = []): static
    {
        return new static('', $status, $headers);
    }

    public function type(string $mime): static
    {
        $this->setHeaderValue('Content-Type', $mime);

        return $this;
    }

    public function withType(string $mime): static
    {
        $clone = clone $this;
        $clone->setHeaderValue('Content-Type', $mime);

        return $clone;
    }

    public function isJson(): bool
    {
        $contentType = $this->findHeaderValue('Content-Type');

        return $contentType !== null
            && str_contains(strtolower($contentType), 'application/json');
    }

    public function send(): void
    {
        $this->sendInternal(false);
    }

    public function sendFor(Request $request): void
    {
        $isHead = strtoupper($request->method()) === 'HEAD';

        $this->sendInternal($isHead);
    }

    public function sendHead(): void
    {
        $this->sendInternal(true);
    }

    public function __toString(): string
    {
        return $this->content;
    }
}
