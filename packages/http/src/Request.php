<?php

namespace Codemonster\Http;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/** @phpstan-consistent-constructor */
class Request implements ServerRequestInterface
{
    /** @var list<string> */
    protected static array $trustedProxies = [];
    protected string $protocolVersion = '1.1';
    protected string $method;
    protected string $uri;
    /** @var array<string|int, mixed> */
    protected array $query = [];
    /** @var array<string|int, mixed> */
    protected array $body = [];
    /** @var array<string|int, mixed> */
    protected array $files = [];
    /** @var array<string, string|string[]> */
    protected array $headers = [];
    protected string $rawBody = '';
    /** @var array<string, mixed> */
    protected array $server = [];
    /** @var array<string, string|string[]> */
    protected array $normalizedHeaders = [];
    /** @var array<string, mixed> */
    protected array $attributes = [];
    /** @var array<string, mixed> */
    protected array $cookies = [];
    /** @var array<string|int, mixed>|object|null */
    protected array|object|null $parsedBody = null;
    protected ?StreamInterface $stream = null;
    protected ?UriInterface $psrUri = null;
    protected ?string $requestTarget = null;

    /** @param list<string> $proxies */
    public static function setTrustedProxies(array $proxies): void
    {
        self::$trustedProxies = $proxies;
    }

    /** @return list<string> */
    public static function getTrustedProxies(): array
    {
        return self::$trustedProxies;
    }

    private static function isTrustedProxy(?string $ip): bool
    {
        if ($ip === null || $ip === '') {
            return false;
        }

        if (in_array($ip, self::$trustedProxies, true)) {
            return true;
        }

        foreach (self::$trustedProxies as $proxy) {
            if (
                str_contains($proxy, '/')
                && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
                && self::ipv4InSubnet($ip, $proxy)
            ) {
                return true;
            }

            if (
                str_contains($proxy, '/')
                && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)
                && self::ipv6InSubnet($ip, $proxy)
            ) {
                return true;
            }
        }

        return false;
    }

    private static function ipv4InSubnet(string $ip, string $cidr): bool
    {
        $parts = explode('/', $cidr, 2);
        $subnet = $parts[0] ?? '';
        $mask = isset($parts[1]) ? (int) $parts[1] : 32;

        if (!filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);

        if ($ipLong === false || $subnetLong === false) {
            return false;
        }

        $mask = max(0, min(32, $mask));
        $maskLong = $mask === 0 ? 0 : ((~0 << (32 - $mask)) & 0xffffffff);

        return (($ipLong & $maskLong) === ($subnetLong & $maskLong));
    }

    private static function ipv6InSubnet(string $ip, string $cidr): bool
    {
        $parts = explode('/', $cidr, 2);
        $subnet = $parts[0] ?? '';
        $mask = isset($parts[1]) ? (int) $parts[1] : 128;

        if (!filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return false;
        }

        $ipBin = inet_pton($ip);
        $subnetBin = inet_pton($subnet);

        if ($ipBin === false || $subnetBin === false) {
            return false;
        }

        $mask = max(0, min(128, $mask));
        $bytes = intdiv($mask, 8);
        $bits = $mask % 8;

        if ($bytes > 0 && substr($ipBin, 0, $bytes) !== substr($subnetBin, 0, $bytes)) {
            return false;
        }

        if ($bits === 0) {
            return true;
        }

        $maskByte = (0xFF << (8 - $bits)) & 0xFF;
        $ipByte = ord($ipBin[$bytes]);
        $subnetByte = ord($subnetBin[$bytes]);

        return (($ipByte & $maskByte) === ($subnetByte & $maskByte));
    }

    /**
     * @param array<string|int, mixed> $files
     * @return array<string|int, mixed>
     */
    private static function normalizeFiles(array $files): array
    {
        $normalized = [];

        foreach ($files as $field => $data) {
            if (!is_array($data) || !isset($data['name'], $data['type'], $data['tmp_name'], $data['error'], $data['size'])) {
                $normalized[$field] = $data;

                continue;
            }

            $normalized[$field] = self::normalizeFileEntry(
                $data['name'],
                $data['type'],
                $data['tmp_name'],
                $data['error'],
                $data['size']
            );
        }

        return $normalized;
    }

    private static function normalizeFileEntry(mixed $name, mixed $type, mixed $tmpName, mixed $error, mixed $size): mixed
    {
        if (is_array($name)) {
            $result = [];

            foreach ($name as $key => $value) {
                $result[$key] = self::normalizeFileEntry(
                    $value,
                    is_array($type) ? ($type[$key] ?? null) : null,
                    is_array($tmpName) ? ($tmpName[$key] ?? null) : null,
                    is_array($error) ? ($error[$key] ?? null) : null,
                    is_array($size) ? ($size[$key] ?? null) : null
                );
            }

            return $result;
        }

        return [
            'name' => $name,
            'type' => $type,
            'tmp_name' => $tmpName,
            'error' => $error,
            'size' => $size,
        ];
    }

    /**
     * @param array<string, string|string[]> $headers
     * @return array<string, string|string[]>
     */
    private static function normalizeHeaders(array $headers): array
    {
        $normalized = [];

        foreach ($headers as $name => $value) {
            if (is_string($name)) {
                $normalized[strtolower($name)] = $value;
            }
        }

        return $normalized;
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

    /**
     * @param array<string|int, mixed> $query
     * @param array<string|int, mixed> $body
     * @param array<string, string|string[]> $headers
     * @param array<string, mixed> $server
     * @param array<string|int, mixed> $files
     */
    public function __construct(
        string $method,
        string $uri,
        array $query = [],
        array $body = [],
        array $headers = [],
        string $rawBody = '',
        array $server = [],
        array $files = []
    ) {
        $this->method = strtoupper($method);
        $path = parse_url($uri, PHP_URL_PATH);
        $this->uri = is_string($path) ? $path : '/';
        $this->psrUri = new Uri($uri);
        $this->query = $query;
        $this->body = $body;
        $this->parsedBody = $body;
        $this->files = self::normalizeFiles($files);
        $this->headers = $headers;
        $this->rawBody = $rawBody;
        $this->server = $server;
        $this->normalizedHeaders = self::normalizeHeaders($headers);

        if (empty($this->body) && $this->rawBody !== '') {
            $contentType = $this->normalizedHeaders['content-type'] ?? '';
            $contentType = is_array($contentType) ? implode(', ', $contentType) : $contentType;

            if (stripos($contentType, 'application/x-www-form-urlencoded') !== false) {
                parse_str($this->rawBody, $parsedBody);

                $this->body = $parsedBody;
            }
        }

        $this->parsedBody = $this->body;
    }

    public static function capture(): static
    {
        $headers = [];

        foreach ($_SERVER as $key => $value) {
            if (is_string($key) && is_string($value) && str_starts_with($key, 'HTTP_')) {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));

                $headers[$name] = $value;
            }
        }

        if (!isset($headers['Content-Type']) && is_string($_SERVER['CONTENT_TYPE'] ?? null)) {
            $headers['Content-Type'] = $_SERVER['CONTENT_TYPE'];
        }

        if (!isset($headers['Content-Length']) && is_string($_SERVER['CONTENT_LENGTH'] ?? null)) {
            $headers['Content-Length'] = $_SERVER['CONTENT_LENGTH'];
        }

        if (!isset($headers['Authorization'])) {
            $auth = $_SERVER['HTTP_AUTHORIZATION']
                ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
                ?? $_SERVER['AUTHORIZATION']
                ?? null;

            if (is_string($auth)) {
                $headers['Authorization'] = $auth;
            }
        }

        $rawBody = file_get_contents('php://input');

        if ($rawBody === false) {
            $rawBody = '';
        }

        $contentType = $headers['Content-Type'] ?? '';
        $body = $_POST;

        if (stripos($contentType, 'application/json') !== false) {
            $json = json_decode($rawBody, true);

            if (is_array($json)) {
                $body = $json;
            }
        } elseif (
            stripos($contentType, 'application/x-www-form-urlencoded') !== false
            && $rawBody !== ''
            && empty($_POST)
        ) {
            parse_str($rawBody, $parsedBody);

            $body = $parsedBody;
        }

        $method = is_string($_SERVER['REQUEST_METHOD'] ?? null) ? $_SERVER['REQUEST_METHOD'] : 'GET';

        if (strtoupper($method) === 'POST') {
            $override = $body['_method'] ?? null;

            foreach ($headers as $name => $value) {
                if (strcasecmp($name, 'X-HTTP-Method-Override') === 0) {
                    $override = $value;
                    break;
                }
            }

            if (is_string($override) && $override !== '') {
                $method = strtoupper($override);
            }
        }

        $requestUri = is_string($_SERVER['REQUEST_URI'] ?? null) ? $_SERVER['REQUEST_URI'] : '/';
        $server = [];
        foreach ($_SERVER as $key => $value) {
            if (is_string($key)) {
                $server[$key] = $value;
            }
        }

        return new static(
            $method,
            $requestUri,
            $_GET,
            $body,
            $headers,
            $rawBody,
            $server,
            $_FILES
        );
    }

    public function method(): string
    {
        return $this->method;
    }

    public function getRequestTarget(): string
    {
        if ($this->requestTarget !== null) {
            return $this->requestTarget;
        }

        $target = $this->uri ?: '/';
        $query = $this->psrUri?->getQuery() ?? '';

        return $query === '' ? $target : $target . '?' . $query;
    }

    public function withRequestTarget(string $requestTarget): static
    {
        if (preg_match('/\s/', $requestTarget) === 1) {
            throw new \InvalidArgumentException('Request target must not contain whitespace.');
        }

        $clone = clone $this;
        $clone->requestTarget = $requestTarget;

        return $clone;
    }

    public function getMethod(): string
    {
        return $this->method();
    }

    public function withMethod(string $method): static
    {
        $clone = clone $this;
        $clone->method = strtoupper($method);

        return $clone;
    }

    public function uri(): string
    {
        return $this->uri;
    }

    public function getUri(): UriInterface
    {
        return $this->psrUri ?? new Uri($this->uri);
    }

    public function withUri(UriInterface $uri, bool $preserveHost = false): static
    {
        $clone = clone $this;
        $clone->psrUri = $uri;
        $clone->uri = $uri->getPath() ?: '/';

        if (!$preserveHost || !$clone->hasHeader('Host')) {
            $host = $uri->getHost();

            if ($host !== '') {
                if ($uri->getPort() !== null) {
                    $host .= ':' . $uri->getPort();
                }

                $clone = $clone->withHeader('Host', $host);
            }
        }

        return $clone;
    }

    public function query(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->query;
        }

        if (str_contains($key, '.')) {
            $value = $this->getByDot($this->query, $key);

            return $value ?? $default;
        }

        return $this->query[$key] ?? $default;
    }

    public function input(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->body;
        }

        if (str_contains($key, '.')) {
            $value = $this->getByDot($this->body, $key);

            return $value ?? $default;
        }

        return $this->body[$key] ?? $default;
    }

    public function files(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->files;
        }

        if (str_contains($key, '.')) {
            $value = $this->getByDot($this->files, $key);

            return $value ?? $default;
        }

        return $this->files[$key] ?? $default;
    }

    public function header(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->headers;
        }

        $key = strtolower($key);

        return $this->normalizedHeaders[$key] ?? $default;
    }

    public function withHeader(string $name, mixed $value): static
    {
        $clone = clone $this;
        $clone->headers[$name] = self::normalizeHeaderValue($value);
        $clone->normalizedHeaders = self::normalizeHeaders($clone->headers);

        return $clone;
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

    public function hasHeader(string $name): bool
    {
        return array_key_exists(strtolower($name), $this->normalizedHeaders);
    }

    public function getHeader(string $name): array
    {
        $value = $this->header($name);

        if ($value === null) {
            return [];
        }

        $normalized = self::normalizeHeaderValue($value);

        return is_array($normalized) ? $normalized : [$normalized];
    }

    public function getHeaderLine(string $name): string
    {
        return implode(', ', $this->getHeader($name));
    }

    public function withAddedHeader(string $name, mixed $value): static
    {
        $clone = clone $this;
        $existing = $clone->getHeader($name);
        $normalized = self::normalizeHeaderValue($value);
        $added = is_array($normalized) ? $normalized : [$normalized];

        return $clone->withHeader($name, array_merge($existing, $added));
    }

    public function getBody(): StreamInterface
    {
        if (!$this->stream) {
            $this->stream = new Stream($this->rawBody);
        }

        return $this->stream;
    }

    public function withBody(StreamInterface $body): static
    {
        $clone = clone $this;
        $clone->stream = $body;
        $clone->rawBody = (string) $body;

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

        $clone->normalizedHeaders = self::normalizeHeaders($clone->headers);

        return $clone;
    }

    /** @param array<string|int, mixed> $query */
    public function withQuery(array $query): static
    {
        $clone = clone $this;
        $clone->query = $query;

        return $clone;
    }

    /** @param array<string|int, mixed> $body */
    public function withInput(array $body): static
    {
        $clone = clone $this;
        $clone->body = $body;
        $clone->parsedBody = $body;

        return $clone;
    }

    /** @return array<string, string|string[]> */
    public function headers(): array
    {
        return $this->headers;
    }

    /** @return array<string, mixed> */
    public function getServerParams(): array
    {
        return $this->server;
    }

    /** @return array<string, mixed> */
    public function getCookieParams(): array
    {
        return $this->cookies;
    }

    /** @param array<string, mixed> $cookies */
    public function withCookieParams(array $cookies): static
    {
        $clone = clone $this;
        $clone->cookies = $cookies;

        return $clone;
    }

    /** @return array<string|int, mixed> */
    public function getQueryParams(): array
    {
        return $this->query;
    }

    /** @param array<string|int, mixed> $query */
    public function withQueryParams(array $query): static
    {
        return $this->withQuery($query);
    }

    /** @return array<string|int, mixed> */
    public function getUploadedFiles(): array
    {
        return $this->files;
    }

    /** @param array<string|int, mixed> $uploadedFiles */
    public function withUploadedFiles(array $uploadedFiles): static
    {
        $clone = clone $this;
        $clone->files = $uploadedFiles;

        return $clone;
    }

    /** @return array<string|int, mixed>|object|null */
    public function getParsedBody(): mixed
    {
        return $this->parsedBody;
    }

    /** @param array<string|int, mixed>|object|null $data */
    public function withParsedBody(mixed $data): static
    {
        $clone = clone $this;
        $clone->parsedBody = $data;
        $clone->body = is_array($data) ? $data : [];

        return $clone;
    }

    /** @return array<string, mixed> */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getAttribute(string $name, mixed $default = null): mixed
    {
        return $this->attributes[$name] ?? $default;
    }

    public function withAttribute(string $name, mixed $value): static
    {
        $clone = clone $this;
        $clone->attributes[$name] = $value;

        return $clone;
    }

    public function withoutAttribute(string $name): static
    {
        $clone = clone $this;
        unset($clone->attributes[$name]);

        return $clone;
    }

    /** @return array<string, mixed> */
    public function server(): array
    {
        return $this->server;
    }

    public function body(): string
    {
        return $this->rawBody;
    }

    public function is(string $method): bool
    {
        return strtoupper($this->method) === strtoupper($method);
    }

    public function wantsJson(): bool
    {
        $accept = $this->header('Accept', '');

        return is_string($accept) && str_contains($accept, 'application/json');
    }

    public function isSecure(): bool
    {
        if (!empty($this->server['HTTPS']) && $this->server['HTTPS'] !== 'off') {
            return true;
        }

        $remoteAddr = $this->server['REMOTE_ADDR'] ?? null;

        if (!self::isTrustedProxy(is_string($remoteAddr) ? $remoteAddr : null)) {
            return false;
        }

        $forwardedProto = $this->server['HTTP_X_FORWARDED_PROTO'] ?? '';

        return is_string($forwardedProto) && strtolower($forwardedProto) === 'https';
    }

    public function ip(): string
    {
        $remoteAddr = $this->server['REMOTE_ADDR'] ?? null;

        if (!self::isTrustedProxy(is_string($remoteAddr) ? $remoteAddr : null)) {
            return is_string($remoteAddr) && $remoteAddr !== '' ? $remoteAddr : '0.0.0.0';
        }

        $candidates = [];

        $forwardedFor = $this->server['HTTP_X_FORWARDED_FOR'] ?? null;
        if (is_string($forwardedFor) && $forwardedFor !== '') {
            $parts = explode(',', $forwardedFor);

            foreach ($parts as $part) {
                $candidates[] = trim($part);
            }
        }

        $clientIp = $this->server['HTTP_CLIENT_IP'] ?? null;
        if (is_string($clientIp) && $clientIp !== '') {
            $candidates[] = $clientIp;
        }

        if (is_string($remoteAddr) && $remoteAddr !== '') {
            $candidates[] = $remoteAddr;
        }

        foreach ($candidates as $candidate) {
            if (filter_var($candidate, FILTER_VALIDATE_IP)) {
                return $candidate;
            }
        }

        return '0.0.0.0';
    }

    public function userAgent(): string
    {
        $userAgent = $this->server['HTTP_USER_AGENT'] ?? null;

        return is_string($userAgent) ? $userAgent : '';
    }

    public function scheme(): string
    {
        return (!empty($this->server['HTTPS']) && $this->server['HTTPS'] !== 'off') ? 'https' : 'http';
    }

    public function host(): string
    {
        $host = $this->server['HTTP_HOST'] ?? null;

        return is_string($host) && $host !== '' ? $host : 'localhost';
    }

    public function fullUrl(): string
    {
        return $this->scheme() . '://' . $this->host() . $this->uri;
    }

    public function fullUrlWithQuery(): string
    {
        if ($this->query === []) {
            return $this->fullUrl();
        }

        return $this->fullUrl() . '?' . http_build_query($this->query);
    }

    /** @return array<string|int, mixed> */
    public function all(): array
    {
        return array_replace_recursive($this->query, $this->body, $this->files);
    }

    /**
     * @param list<string> $keys
     * @return array<string|int, mixed>
     */
    public function only(array $keys): array
    {
        $data = $this->all();
        $result = [];

        foreach ($keys as $key) {
            if (str_contains($key, '.')) {
                $value = $this->getByDot($data, $key);

                if ($value !== null) {
                    $this->setByDot($result, $key, $value);
                }

                continue;
            }

            if (array_key_exists($key, $data)) {
                $result[$key] = $data[$key];
            }
        }

        return $result;
    }

    /**
     * @param list<string> $keys
     * @return array<string|int, mixed>
     */
    public function except(array $keys): array
    {
        $data = $this->all();

        foreach ($keys as $key) {
            if (str_contains($key, '.')) {
                $this->unsetByDot($data, $key);

                continue;
            }

            unset($data[$key]);
        }

        return $data;
    }

    /** @param array<string|int, mixed> $data */
    private function getByDot(array $data, string $path): mixed
    {
        $parts = explode('.', $path);

        foreach ($parts as $part) {
            if (!is_array($data) || !array_key_exists($part, $data)) {
                return null;
            }

            $data = $data[$part];
        }

        return $data;
    }

    /** @param array<string|int, mixed> $data */
    private function setByDot(array &$data, string $path, mixed $value): void
    {
        $parts = explode('.', $path);
        $cursor = &$data;

        foreach ($parts as $part) {
            if (!isset($cursor[$part]) || !is_array($cursor[$part])) {
                $cursor[$part] = [];
            }

            $cursor = &$cursor[$part];
        }

        $cursor = $value;
    }

    /** @param array<string|int, mixed> $data */
    private function unsetByDot(array &$data, string $path): void
    {
        $parts = explode('.', $path);
        $cursor = &$data;

        foreach ($parts as $index => $part) {
            if (!is_array($cursor) || !array_key_exists($part, $cursor)) {
                return;
            }

            if ($index === count($parts) - 1) {
                unset($cursor[$part]);

                return;
            }

            $cursor = &$cursor[$part];
        }
    }
}
