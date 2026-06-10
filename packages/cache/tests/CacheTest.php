<?php

namespace Codemonster\Cache\Tests;

use Codemonster\Cache\ArrayCache;
use Codemonster\Cache\CacheManager;
use Codemonster\Cache\FileCache;
use Codemonster\Cache\RedisCache;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

class CacheTest extends TestCase
{
    public function test_array_cache_implements_psr16_contract(): void
    {
        $cache = new ArrayCache();

        self::assertInstanceOf(CacheInterface::class, $cache);
        self::assertTrue($cache->set('name', 'annabel'));
        self::assertTrue($cache->has('name'));
        self::assertSame('annabel', $cache->get('name'));
        self::assertTrue($cache->delete('name'));
        self::assertSame('fallback', $cache->get('name', 'fallback'));
    }

    public function test_array_cache_supports_multiple_operations(): void
    {
        $cache = new ArrayCache();

        self::assertTrue($cache->setMultiple(['a' => 1, 'b' => 2]));
        self::assertSame(['a' => 1, 'b' => 2, 'c' => null], $cache->getMultiple(['a', 'b', 'c']));
        self::assertTrue($cache->deleteMultiple(['a', 'b']));
        self::assertFalse($cache->has('a'));
    }

    public function test_array_cache_expires_items(): void
    {
        $cache = new ArrayCache();

        $cache->set('short', 'value', 0);

        self::assertFalse($cache->has('short'));
        self::assertSame('missing', $cache->get('short', 'missing'));
    }

    public function test_cache_add_only_sets_missing_items(): void
    {
        $cache = new ArrayCache();

        self::assertTrue($cache->add('lock', 'first'));
        self::assertFalse($cache->add('lock', 'second'));
        self::assertSame('first', $cache->get('lock'));
    }

    public function test_invalid_key_throws_psr_exception(): void
    {
        $cache = new ArrayCache();

        $this->expectException(InvalidArgumentException::class);

        $cache->get('bad/key');
    }

    public function test_file_cache_persists_values(): void
    {
        $path = sys_get_temp_dir() . '/annabel-cache-' . bin2hex(random_bytes(6));
        $cache = new FileCache($path);

        try {
            self::assertTrue($cache->set('name', 'annabel'));
            self::assertSame('annabel', (new FileCache($path))->get('name'));
            self::assertTrue($cache->clear());
            self::assertFalse($cache->has('name'));
        } finally {
            if (is_dir($path)) {
                @rmdir($path);
            }
        }
    }

    public function test_redis_cache_uses_prefixed_keys(): void
    {
        $redis = new FakeRedisClient();
        $cache = new RedisCache($redis, 'app:');

        self::assertTrue($cache->set('name', 'annabel'));
        self::assertTrue($cache->set('temporary', 'value', 0));
        self::assertTrue($cache->add('lock', 'first'));
        self::assertFalse($cache->add('lock', 'second'));
        $redis->set('other:name', serialize('other'));

        self::assertSame('annabel', $cache->get('name'));
        self::assertSame('first', $cache->get('lock'));
        self::assertFalse($cache->has('temporary'));
        self::assertSame('missing', $cache->get('temporary', 'missing'));
        self::assertTrue($cache->clear());
        self::assertFalse($cache->has('name'));
        self::assertSame(serialize('other'), $redis->get('other:name'));
    }

    public function test_manager_resolves_configured_stores(): void
    {
        $path = sys_get_temp_dir() . '/annabel-cache-manager-' . bin2hex(random_bytes(6));
        $redis = new FakeRedisClient();
        $manager = new CacheManager([
            'default' => 'array',
            'stores' => [
                'array' => [
                    'driver' => 'array',
                ],
                'file' => [
                    'driver' => 'file',
                    'path' => $path,
                ],
                'redis' => [
                    'driver' => 'redis',
                    'client' => $redis,
                    'prefix' => 'manager:',
                ],
            ],
        ]);

        try {
            $manager->store()->set('name', 'annabel');
            $manager->store('file')->set('persisted', 'yes');
            $manager->store('redis')->set('shared', 'redis');

            self::assertSame(['array', 'file', 'redis'], $manager->stores());
            self::assertSame('annabel', $manager->store()->get('name'));
            self::assertSame('yes', (new FileCache($path))->get('persisted'));
            self::assertSame('redis', $manager->store('redis')->get('shared'));
        } finally {
            $manager->store('file')->clear();

            if (is_dir($path)) {
                @rmdir($path);
            }
        }
    }
}

class FakeRedisClient
{
    /** @var array<string, string> */
    private array $items = [];

    public function get(string $key): string|false
    {
        return $this->items[$key] ?? false;
    }

    /**
     * @param array<int|string, mixed> $options
     */
    public function set(string $key, string $value, array $options = []): bool
    {
        if (in_array('nx', $options, true) && array_key_exists($key, $this->items)) {
            return false;
        }

        $this->items[$key] = $value;

        return true;
    }

    public function setex(string $key, int $ttl, string $value): bool
    {
        if ($ttl <= 0) {
            unset($this->items[$key]);

            return true;
        }

        $this->items[$key] = $value;

        return true;
    }

    public function del(string $key): int
    {
        $exists = array_key_exists($key, $this->items);
        unset($this->items[$key]);

        return $exists ? 1 : 0;
    }

    public function exists(string $key): int
    {
        return array_key_exists($key, $this->items) ? 1 : 0;
    }

    /** @return list<string> */
    public function keys(string $pattern): array
    {
        $prefix = rtrim($pattern, '*');

        return array_values(array_filter(
            array_keys($this->items),
            static fn (string $key): bool => str_starts_with($key, $prefix),
        ));
    }
}
