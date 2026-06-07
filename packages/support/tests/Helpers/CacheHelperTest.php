<?php

namespace Tests\Helpers;

use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;

class CacheHelperTest extends TestCase
{
    protected function setUp(): void
    {
        app()->instance(CacheInterface::class, new FakeCache());
    }

    public function testCacheReturnsInstance()
    {
        $this->assertInstanceOf(CacheInterface::class, cache());
    }

    public function testCacheCanStoreAndRetrieveValues()
    {
        cache('name', 'annabel');

        $this->assertSame('annabel', cache('name'));
    }
}

class FakeCache implements CacheInterface
{
    public array $items = [];

    public function get(string $key, mixed $default = null): mixed { return $this->items[$key] ?? $default; }
    public function set(string $key, mixed $value, \DateInterval|int|null $ttl = null): bool { $this->items[$key] = $value; return true; }
    public function delete(string $key): bool { unset($this->items[$key]); return true; }
    public function clear(): bool { $this->items = []; return true; }
    public function getMultiple(iterable $keys, mixed $default = null): iterable { return []; }
    public function setMultiple(iterable $values, \DateInterval|int|null $ttl = null): bool { return true; }
    public function deleteMultiple(iterable $keys): bool { return true; }
    public function has(string $key): bool { return array_key_exists($key, $this->items); }
}
