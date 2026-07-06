<?php

declare(strict_types=1);

namespace Codemonster\Support\Tests\Helpers;

use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;

class CacheHelperTest extends TestCase
{
    protected function setUp(): void
    {
        app()->instance(CacheInterface::class, new FakeCache());
    }

    public function testCacheReturnsInstance(): void
    {
        $this->assertInstanceOf(CacheInterface::class, cache());
    }

    public function testCacheCanStoreAndRetrieveValues(): void
    {
        cache('name', 'annabel');

        $this->assertSame('annabel', cache('name'));
    }
}

class FakeCache implements CacheInterface
{
    /** @var array<string, mixed> */
    public array $items = [];

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->items[$key] ?? $default;
    }
    public function set(string $key, mixed $value, \DateInterval|int|null $ttl = null): bool
    {
        $this->items[$key] = $value;

        return true;
    }
    public function delete(string $key): bool
    {
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
        return [];
    }
    /** @param iterable<string, mixed> $values */
    public function setMultiple(iterable $values, \DateInterval|int|null $ttl = null): bool
    {
        return true;
    }
    public function deleteMultiple(iterable $keys): bool
    {
        return true;
    }
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->items);
    }
}
