<?php

namespace Codemonster\Cache;

use Codemonster\Cache\Exceptions\CacheException;
use DateInterval;

class RedisCache extends ArrayCache
{
    public function __construct(
        protected object $client,
        protected string $prefix = 'cache:',
    ) {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->assertValidKey($key);
        $value = $this->invoke('get', $this->key($key));

        if (!is_string($value)) {
            return $default;
        }

        $decoded = @unserialize($value);

        return $decoded === false && $value !== serialize(false) ? $default : $decoded;
    }

    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        $this->assertValidKey($key);
        $ttl = $this->seconds($ttl);

        if ($ttl !== null && $ttl <= 0) {
            return $this->delete($key);
        }

        $payload = serialize($value);
        $result = $ttl === null
            ? $this->invoke('set', $this->key($key), $payload)
            : $this->invoke('setex', $this->key($key), $ttl, $payload);

        return $result === true || $result === 'OK';
    }

    public function add(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        $this->assertValidKey($key);
        $ttl = $this->seconds($ttl);

        if ($ttl !== null && $ttl <= 0) {
            return false;
        }

        $options = ['nx'];
        if ($ttl !== null) {
            $options['ex'] = $ttl;
        }

        $result = $this->invoke('set', $this->key($key), serialize($value), $options);

        return $result === true || $result === 'OK';
    }

    public function delete(string $key): bool
    {
        $this->assertValidKey($key);
        $this->invoke('del', $this->key($key));

        return true;
    }

    public function clear(): bool
    {
        foreach ($this->keys() as $key) {
            $this->invoke('del', $key);
        }

        return true;
    }

    public function has(string $key): bool
    {
        $this->assertValidKey($key);
        $exists = $this->invoke('exists', $this->key($key));

        return $exists === true || $exists === 1 || $exists === '1';
    }

    protected function key(string $key): string
    {
        return $this->prefix . $key;
    }

    private function invoke(string $method, mixed ...$arguments): mixed
    {
        $callable = [$this->client, $method];

        if (!is_callable($callable)) {
            throw new CacheException("Redis client does not support [{$method}].");
        }

        return $callable(...$arguments);
    }

    /** @return list<string> */
    private function keys(): array
    {
        $keys = $this->invoke('keys', $this->prefix . '*');

        if (!is_array($keys)) {
            return [];
        }

        return array_values(array_filter($keys, 'is_string'));
    }

    private function seconds(DateInterval|int|null $ttl): ?int
    {
        if ($ttl === null) {
            return null;
        }

        if ($ttl instanceof DateInterval) {
            return (new \DateTimeImmutable())->add($ttl)->getTimestamp() - time();
        }

        return $ttl;
    }
}
