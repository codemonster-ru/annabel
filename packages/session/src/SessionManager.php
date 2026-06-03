<?php

namespace Codemonster\Session;

class SessionManager
{
    private Store $store;

    public function __construct(Store $store)
    {
        $this->store = $store;
    }

    public function store(): Store
    {
        return $this->store;
    }

    public function regenerate(bool $destroyOld = true): void
    {
        $this->store->regenerateId($destroyOld);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->store->get($key, $default);
    }

    public function has(string $key): bool
    {
        return $this->store->has($key);
    }

    public function put(string $key, mixed $value): void
    {
        $this->store->put($key, $value);
    }

    public function putWithTtl(string $key, mixed $value, int $ttlSeconds): void
    {
        $this->store->putWithTtl($key, $value, $ttlSeconds);
    }

    public function ttl(string $key): ?int
    {
        return $this->store->ttl($key);
    }

    public function expiresAt(string $key): ?int
    {
        return $this->store->expiresAt($key);
    }

    public function touch(string $key, int $ttlSeconds): bool
    {
        return $this->store->touch($key, $ttlSeconds);
    }

    public function forget(string $key): void
    {
        $this->store->forget($key);
    }

    /**
     * @param array<int, string> $keys
     */
    public function forgetMany(array $keys): void
    {
        $this->store->forgetMany($keys);
    }

    public function pull(string $key, mixed $default = null): mixed
    {
        return $this->store->pull($key, $default);
    }

    public function increment(string $key, int $by = 1): int
    {
        return $this->store->increment($key, $by);
    }

    public function flash(string $key, mixed $value): void
    {
        $this->store->flash($key, $value);
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->store->all();
    }

    public function destroy(bool $clearCookie = true): void
    {
        $this->store->destroy($clearCookie);
    }

    public function sweepExpired(): void
    {
        $this->store->sweepExpired();
    }

    /**
     * @param array<int, string> $previousKeys
     */
    public function rotateEncryptionKey(
        string $newKey,
        array $previousKeys = [],
        bool $allowPlaintext = false
    ): void {
        $this->store->rotateEncryptionKey($newKey, $previousKeys, $allowPlaintext);
    }

    public function for(string $namespace, string $delimiter = '.'): SessionScope
    {
        return new SessionScope($this->store, $namespace, $delimiter);
    }

    /**
     * @return array<int, string>
     */
    public function keys(?string $prefix = null): array
    {
        return $this->store->keys($prefix);
    }

    /**
     * @return array<int, string>
     */
    public function keysMatch(string $pattern): array
    {
        return $this->store->keysMatch($pattern);
    }

    public function count(): int
    {
        return $this->store->count();
    }

    public function size(): int
    {
        return $this->store->size();
    }

    public function forgetNamespace(string $prefix): void
    {
        $this->store->forgetNamespace($prefix);
    }

    public function touchAll(int $ttlSeconds, ?string $prefix = null): int
    {
        return $this->store->touchAll($ttlSeconds, $prefix);
    }

    /**
     * @param array<int, string> $redactKeys
     * @param array<int, string> $redactPatterns
     * @return array<string, mixed>
     */
    public function dump(array $redactKeys = [], array $redactPatterns = []): array
    {
        return $this->store->dump($redactKeys, $redactPatterns);
    }
}
