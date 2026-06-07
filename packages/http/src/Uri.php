<?php

namespace Codemonster\Http;

use Psr\Http\Message\UriInterface;

class Uri implements UriInterface
{
    protected string $scheme = '';
    protected string $userInfo = '';
    protected string $host = '';
    protected ?int $port = null;
    protected string $path = '';
    protected string $query = '';
    protected string $fragment = '';

    public function __construct(string $uri = '')
    {
        $parts = parse_url($uri);

        if ($parts === false) {
            throw new \InvalidArgumentException("Invalid URI: {$uri}");
        }

        $this->scheme = isset($parts['scheme']) ? strtolower($parts['scheme']) : '';
        $this->host = isset($parts['host']) ? strtolower($parts['host']) : '';
        $this->port = $parts['port'] ?? null;
        $this->path = $parts['path'] ?? '';
        $this->query = $parts['query'] ?? '';
        $this->fragment = $parts['fragment'] ?? '';

        if (isset($parts['user'])) {
            $this->userInfo = $parts['user'];

            if (isset($parts['pass'])) {
                $this->userInfo .= ':' . $parts['pass'];
            }
        }
    }

    public function getScheme(): string { return $this->scheme; }
    public function getAuthority(): string
    {
        $authority = $this->host;

        if ($this->userInfo !== '') {
            $authority = $this->userInfo . '@' . $authority;
        }

        if ($this->port !== null && !$this->isDefaultPort()) {
            $authority .= ':' . $this->port;
        }

        return $authority;
    }
    public function getUserInfo(): string { return $this->userInfo; }
    public function getHost(): string { return $this->host; }
    public function getPort(): ?int { return $this->isDefaultPort() ? null : $this->port; }
    public function getPath(): string { return $this->path; }
    public function getQuery(): string { return $this->query; }
    public function getFragment(): string { return $this->fragment; }

    public function withScheme(string $scheme): static
    {
        $clone = clone $this;
        $clone->scheme = strtolower($scheme);

        return $clone;
    }

    public function withUserInfo(string $user, ?string $password = null): static
    {
        $clone = clone $this;
        $clone->userInfo = $password === null ? $user : $user . ':' . $password;

        return $clone;
    }

    public function withHost(string $host): static
    {
        $clone = clone $this;
        $clone->host = strtolower($host);

        return $clone;
    }

    public function withPort(?int $port): static
    {
        if ($port !== null && ($port < 1 || $port > 65535)) {
            throw new \InvalidArgumentException('URI port must be between 1 and 65535.');
        }

        $clone = clone $this;
        $clone->port = $port;

        return $clone;
    }

    public function withPath(string $path): static
    {
        $clone = clone $this;
        $clone->path = $path;

        return $clone;
    }

    public function withQuery(string $query): static
    {
        $clone = clone $this;
        $clone->query = ltrim($query, '?');

        return $clone;
    }

    public function withFragment(string $fragment): static
    {
        $clone = clone $this;
        $clone->fragment = ltrim($fragment, '#');

        return $clone;
    }

    public function __toString(): string
    {
        $uri = '';

        if ($this->scheme !== '') {
            $uri .= $this->scheme . ':';
        }

        if ($this->getAuthority() !== '') {
            $uri .= '//' . $this->getAuthority();
        }

        $uri .= $this->path;

        if ($this->query !== '') {
            $uri .= '?' . $this->query;
        }

        if ($this->fragment !== '') {
            $uri .= '#' . $this->fragment;
        }

        return $uri;
    }

    protected function isDefaultPort(): bool
    {
        return ($this->scheme === 'http' && $this->port === 80)
            || ($this->scheme === 'https' && $this->port === 443);
    }
}
