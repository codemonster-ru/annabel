<?php

namespace Codemonster\Annabel\Cache;

use DateInterval;
use Psr\SimpleCache\CacheInterface;

class ArrayCache implements CacheInterface
{
    /** @var array<string, array{value: mixed, expires_at: int|null}> */
    protected array $items = [];

    public function get(string $key, mixed $default = null): mixed
    {
        $this->assertValidKey($key);

        if (!$this->has($key)) {
            return $default;
        }

        return $this->items[$key]['value'];
    }

    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        $this->assertValidKey($key);

        $this->items[$key] = [
            'value' => $value,
            'expires_at' => $this->expiresAt($ttl),
        ];

        return true;
    }

    public function delete(string $key): bool
    {
        $this->assertValidKey($key);

        unset($this->items[$key]);

        return true;
    }

    public function clear(): bool
    {
        $this->items = [];

        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $values = [];

        foreach ($keys as $key) {
            if (!is_string($key)) {
                throw new InvalidCacheKeyException('Cache key must be a string.');
            }

            $values[$key] = $this->get($key, $default);
        }

        return $values;
    }

    /**
     * @param iterable<string, mixed> $values
     */
    public function setMultiple(iterable $values, DateInterval|int|null $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            if (!is_string($key)) {
                throw new InvalidCacheKeyException('Cache key must be a string.');
            }

            $this->set($key, $value, $ttl);
        }

        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            if (!is_string($key)) {
                throw new InvalidCacheKeyException('Cache key must be a string.');
            }

            $this->delete($key);
        }

        return true;
    }

    public function has(string $key): bool
    {
        $this->assertValidKey($key);

        if (!array_key_exists($key, $this->items)) {
            return false;
        }

        $expiresAt = $this->items[$key]['expires_at'];

        if (is_int($expiresAt) && $expiresAt <= time()) {
            unset($this->items[$key]);

            return false;
        }

        return true;
    }

    protected function assertValidKey(string $key): void
    {
        if ($key === '' || preg_match('/[{}()\/\\\\@:]/', $key)) {
            throw new InvalidCacheKeyException("Invalid cache key: {$key}");
        }
    }

    protected function expiresAt(DateInterval|int|null $ttl): ?int
    {
        if ($ttl === null) {
            return null;
        }

        if ($ttl instanceof DateInterval) {
            return (new \DateTimeImmutable())->add($ttl)->getTimestamp();
        }

        return time() + max(0, $ttl);
    }
}
