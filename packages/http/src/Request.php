<?php

namespace Codemonster\Http;

class Request
{
    protected static array $trustedProxies = [];
    protected string $method;
    protected string $uri;
    protected array $query = [];
    protected array $body = [];
    protected array $files = [];
    protected array $headers = [];
    protected string $rawBody = '';
    protected array $server = [];
    protected array $normalizedHeaders = [];

    public static function setTrustedProxies(array $proxies): void
    {
        self::$trustedProxies = $proxies;
    }

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
                    $type[$key] ?? null,
                    $tmpName[$key] ?? null,
                    $error[$key] ?? null,
                    $size[$key] ?? null
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
        $this->uri = parse_url($uri, PHP_URL_PATH) ?? '/';
        $this->query = $query;
        $this->body = $body;
        $this->files = self::normalizeFiles($files);
        $this->headers = $headers;
        $this->rawBody = $rawBody;
        $this->server = $server;
        $this->normalizedHeaders = self::normalizeHeaders($headers);

        if (empty($this->body) && $this->rawBody !== '') {
            $contentType = $this->normalizedHeaders['content-type'] ?? '';

            if (stripos($contentType, 'application/x-www-form-urlencoded') !== false) {
                parse_str($this->rawBody, $parsedBody);

                if (is_array($parsedBody)) {
                    $this->body = $parsedBody;
                }
            }
        }
    }

    public static function capture(): static
    {
        $headers = [];

        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));

                $headers[$name] = $value;
            }
        }

        if (!isset($headers['Content-Type']) && isset($_SERVER['CONTENT_TYPE'])) {
            $headers['Content-Type'] = $_SERVER['CONTENT_TYPE'];
        }

        if (!isset($headers['Content-Length']) && isset($_SERVER['CONTENT_LENGTH'])) {
            $headers['Content-Length'] = $_SERVER['CONTENT_LENGTH'];
        }

        if (!isset($headers['Authorization'])) {
            $auth = $_SERVER['HTTP_AUTHORIZATION']
                ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
                ?? $_SERVER['AUTHORIZATION']
                ?? null;

            if ($auth !== null) {
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

            if (is_array($parsedBody)) {
                $body = $parsedBody;
            }
        }

        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

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

        return new static(
            $method,
            $_SERVER['REQUEST_URI'] ?? '/',
            $_GET ?? [],
            $body,
            $headers,
            $rawBody,
            $_SERVER,
            $_FILES ?? []
        );
    }

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
        $clone->headers[$name] = $value;
        $clone->normalizedHeaders = self::normalizeHeaders($clone->headers);

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

    public function withQuery(array $query): static
    {
        $clone = clone $this;
        $clone->query = $query;

        return $clone;
    }

    public function withInput(array $body): static
    {
        $clone = clone $this;
        $clone->body = $body;

        return $clone;
    }

    public function headers(): array
    {
        return $this->headers;
    }

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

        return str_contains($accept, 'application/json');
    }

    public function isSecure(): bool
    {
        if (!empty($this->server['HTTPS']) && $this->server['HTTPS'] !== 'off') {
            return true;
        }

        $remoteAddr = $this->server['REMOTE_ADDR'] ?? null;

        if (!self::isTrustedProxy($remoteAddr)) {
            return false;
        }

        $forwardedProto = $this->server['HTTP_X_FORWARDED_PROTO'] ?? '';

        return strtolower($forwardedProto) === 'https';
    }

    public function ip(): string
    {
        $remoteAddr = $this->server['REMOTE_ADDR'] ?? null;

        if (!self::isTrustedProxy($remoteAddr)) {
            return (string) ($remoteAddr ?? '0.0.0.0');
        }

        $candidates = [];

        if (!empty($this->server['HTTP_X_FORWARDED_FOR'])) {
            $parts = explode(',', $this->server['HTTP_X_FORWARDED_FOR']);

            foreach ($parts as $part) {
                $candidates[] = trim($part);
            }
        }

        if (!empty($this->server['HTTP_CLIENT_IP'])) {
            $candidates[] = $this->server['HTTP_CLIENT_IP'];
        }

        if (!empty($this->server['REMOTE_ADDR'])) {
            $candidates[] = $this->server['REMOTE_ADDR'];
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
        return $this->server['HTTP_USER_AGENT'] ?? '';
    }

    public function scheme(): string
    {
        return (!empty($this->server['HTTPS']) && $this->server['HTTPS'] !== 'off') ? 'https' : 'http';
    }

    public function host(): string
    {
        return $this->server['HTTP_HOST'] ?? 'localhost';
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

    public function all(): array
    {
        return array_replace_recursive($this->query, $this->body, $this->files);
    }

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
