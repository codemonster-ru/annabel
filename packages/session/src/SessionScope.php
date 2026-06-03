<?php

namespace Codemonster\Session;

class SessionScope
{
    private Store $store;
    private string $prefix;

    public function __construct(Store $store, string $namespace, string $delimiter = '.')
    {
        $this->store = $store;
        $this->prefix = rtrim($namespace, $delimiter) . $delimiter;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->store->get($this->prefix . $key, $default);
    }

    public function has(string $key): bool
    {
        return $this->store->has($this->prefix . $key);
    }

    public function put(string $key, mixed $value): void
    {
        $this->store->put($this->prefix . $key, $value);
    }

    public function putWithTtl(string $key, mixed $value, int $ttlSeconds): void
    {
        $this->store->putWithTtl($this->prefix . $key, $value, $ttlSeconds);
    }

    public function ttl(string $key): ?int
    {
        return $this->store->ttl($this->prefix . $key);
    }

    public function expiresAt(string $key): ?int
    {
        return $this->store->expiresAt($this->prefix . $key);
    }

    public function touch(string $key, int $ttlSeconds): bool
    {
        return $this->store->touch($this->prefix . $key, $ttlSeconds);
    }

    public function forget(string $key): void
    {
        $this->store->forget($this->prefix . $key);
    }

    /**
     * @param array<int, string> $keys
     */
    public function forgetMany(array $keys): void
    {
        $prefixed = [];
        foreach ($keys as $key) {
            $prefixed[] = $this->prefix . $key;
        }

        $this->store->forgetMany($prefixed);
    }

    public function pull(string $key, mixed $default = null): mixed
    {
        return $this->store->pull($this->prefix . $key, $default);
    }

    public function increment(string $key, int $by = 1): int
    {
        return $this->store->increment($this->prefix . $key, $by);
    }

    public function flash(string $key, mixed $value): void
    {
        $this->store->flash($this->prefix . $key, $value);
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $all = $this->store->all();
        $filtered = [];

        foreach ($all as $key => $_value) {
            if (!is_string($key) || str_starts_with($key, '__')) {
                continue;
            }
            if (str_starts_with($key, $this->prefix)) {
                $filtered[substr($key, strlen($this->prefix))] = $_value;
            }
        }

        return $filtered;
    }

    /**
     * @return array<int, string>
     */
    public function keys(): array
    {
        $keys = $this->store->keys($this->prefix);
        $trimmed = [];

        foreach ($keys as $key) {
            $trimmed[] = substr($key, strlen($this->prefix));
        }

        return $trimmed;
    }

    /**
     * @return array<int, string>
     */
    public function keysMatch(string $pattern): array
    {
        $pattern = $this->prefix . $pattern;
        $keys = $this->store->keysMatch($pattern);
        $trimmed = [];

        foreach ($keys as $key) {
            $trimmed[] = substr($key, strlen($this->prefix));
        }

        return $trimmed;
    }

    public function count(): int
    {
        return count($this->keys());
    }

    public function size(): int
    {
        $data = $this->all();

        try {
            $payload = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \RuntimeException('Failed to encode session payload.', 0, $e);
        }

        return strlen($payload);
    }

    /**
     * @param array<int, string> $redactKeys
     * @param array<int, string> $redactPatterns
     * @return array<string, mixed>
     */
    public function dump(array $redactKeys = [], array $redactPatterns = []): array
    {
        $data = $this->all();

        if ($redactKeys !== []) {
            $lookup = array_fill_keys($redactKeys, true);
            foreach ($data as $key => $_value) {
                if (isset($lookup[$key])) {
                    $data[$key] = '***';
                }
            }
        }

        if ($redactPatterns !== []) {
            foreach ($data as $key => $_value) {
                foreach ($redactPatterns as $pattern) {
                    if ($this->matchesPattern($key, $pattern)) {
                        $data[$key] = '***';
                        break;
                    }
                }
            }
        }

        return $data;
    }

    private function matchesPattern(string $key, string $pattern): bool
    {
        if ($pattern === '') {
            return false;
        }

        if (strpbrk($pattern, '*?[]') === false) {
            return $key === $pattern;
        }

        if (function_exists('fnmatch')) {
            return fnmatch($pattern, $key);
        }

        $escaped = preg_quote($pattern, '/');
        $regex = '/^' . str_replace(['\*', '\?'], ['.*', '.'], $escaped) . '$/u';

        return preg_match($regex, $key) === 1;
    }

    public function forgetNamespace(): void
    {
        $this->store->forgetNamespace($this->prefix);
    }

    public function touchAll(int $ttlSeconds): int
    {
        return $this->store->touchAll($ttlSeconds, $this->prefix);
    }
}
