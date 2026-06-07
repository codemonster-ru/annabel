<?php

namespace Codemonster\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/** @phpstan-consistent-constructor */
class Response implements ResponseInterface
{
    protected string $protocolVersion = '1.1';
    protected string $content = '';
    protected int $status = 200;
    protected string $reasonPhrase = '';
    /** @var array<string, string|list<string>> */
    protected array $headers = [];
    /** @var array<string, string> */
    protected array $cookies = [];
    protected ?StreamInterface $body = null;

    private static function encodeJson(mixed $data, int $options): string
    {
        $json = json_encode($data, $options);

        if ($json === false) {
            throw new \RuntimeException('Failed to encode JSON: ' . json_last_error_msg());
        }

        return $json;
    }

    /** @return string|list<string>|null */
    private function findHeaderValue(string $name): string|array|null
    {
        foreach ($this->headers as $key => $value) {
            if (strcasecmp($key, $name) === 0) {
                return $value;
            }
        }

        return null;
    }

    private function setHeaderValue(string $name, mixed $value): void
    {
        foreach ($this->headers as $key => $_) {
            if (strcasecmp($key, $name) === 0) {
                unset($this->headers[$key]);

                break;
            }
        }

        $this->headers[$name] = self::normalizeHeaderValue($value);
    }

    /** @return string|list<string> */
    private static function normalizeHeaderValue(mixed $value): string|array
    {
        if (is_array($value)) {
            $normalized = [];
            foreach ($value as $item) {
                if (!is_string($item) && !is_int($item) && !is_float($item)) {
                    throw new \InvalidArgumentException('Header values must be strings or numbers.');
                }
                $normalized[] = (string) $item;
            }

            return $normalized;
        }

        if (!is_string($value) && !is_int($value) && !is_float($value)) {
            throw new \InvalidArgumentException('Header value must be a string or number.');
        }

        return (string) $value;
    }

    /** @param array<string, mixed> $options */
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
            $maxAge = $options['max_age'];
            if (is_int($maxAge) || (is_string($maxAge) && preg_match('/\A-?\d+\z/', $maxAge) === 1)) {
                $cookie .= '; Max-Age=' . (int) $maxAge;
            }
        }

        if (is_string($options['path'] ?? null)) {
            $cookie .= '; Path=' . $options['path'];
        }

        if (is_string($options['domain'] ?? null)) {
            $cookie .= '; Domain=' . $options['domain'];
        }

        if (!empty($options['secure'])) {
            $cookie .= '; Secure';
        }

        if (!empty($options['httponly'])) {
            $cookie .= '; HttpOnly';
        }

        if (is_string($options['samesite'] ?? null)) {
            $sameSite = ucfirst(strtolower($options['samesite']));
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

    /** @param array<string, mixed> $headers */
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

        $this->body = new Stream($this->content);

        return $this;
    }

    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    public function withProtocolVersion(string $version): static
    {
        $clone = clone $this;
        $clone->protocolVersion = $version;

        return $clone;
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

    public function withStatus(int $status, string $reasonPhrase = ''): static
    {
        $clone = clone $this;
        $clone->status = $status;
        $clone->reasonPhrase = $reasonPhrase;

        return $clone;
    }

    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase !== '' ? $this->reasonPhrase : self::reasonPhrase($this->status);
    }

    public function getHeaders(): array
    {
        $headers = [];

        foreach ($this->headers as $name => $value) {
            $headers[$name] = is_array($value)
                ? array_values($value)
                : [$value];
        }

        return $headers;
    }

    public function header(string $name, mixed $value): static
    {
        $this->setHeaderValue($name, $value);

        return $this;
    }

    public function withHeader(string $name, mixed $value): static
    {
        $clone = clone $this;
        $clone->setHeaderValue($name, $value);

        return $clone;
    }

    public function withAddedHeader(string $name, mixed $value): static
    {
        $clone = clone $this;
        $existing = $clone->getHeader($name);
        $added = is_array($value) ? array_values($value) : [(string) $value];

        return $clone->withHeader($name, array_merge($existing, $added));
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

    /** @param array<string, mixed> $options */
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

    /** @param array<string, mixed> $options */
    public function cookie(string $name, string $value, array $options = []): static
    {
        $this->cookies[$name] = self::buildCookieString($name, $value, $options);

        return $this;
    }

    /** @param array<string, mixed> $headers */
    public function setHeaders(array $headers): static
    {
        foreach ($headers as $name => $value) {
            $this->setHeaderValue($name, $value);
        }

        return $this;
    }

    /** @param array<string, mixed> $headers */
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

    public function getHeader(string $name): array
    {
        $value = $this->findHeaderValue($name);

        if ($value === null) {
            return [];
        }

        return is_array($value) ? array_values($value) : [(string) $value];
    }

    public function getHeaderLine(string $name): string
    {
        return implode(', ', $this->getHeader($name));
    }

    public function getBody(): StreamInterface
    {
        if (!$this->body) {
            $this->body = new Stream($this->content);
        }

        return $this->body;
    }

    public function withBody(StreamInterface $body): static
    {
        $clone = clone $this;
        $clone->body = $body;
        $clone->content = (string) $body;

        return $clone;
    }

    /** @param array<string, mixed> $headers */
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

    /** @param array<string, mixed> $headers */
    public static function redirect(string $url, int $status = 302, array $headers = []): static
    {
        $headers = array_merge(['Location' => $url], $headers);

        return new static('', $status, $headers);
    }

    /** @param array<string, mixed> $headers */
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

        if ($contentType === null) {
            return false;
        }

        $contentType = is_array($contentType) ? implode(', ', $contentType) : $contentType;

        return str_contains(strtolower($contentType), 'application/json');
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

    protected static function reasonPhrase(int $status): string
    {
        return [
            100 => 'Continue',
            101 => 'Switching Protocols',
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            204 => 'No Content',
            301 => 'Moved Permanently',
            302 => 'Found',
            304 => 'Not Modified',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            419 => 'Page Expired',
            422 => 'Unprocessable Entity',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
        ][$status] ?? '';
    }
}
