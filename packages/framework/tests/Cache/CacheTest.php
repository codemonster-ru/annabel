<?php

namespace Codemonster\Annabel\Tests\Cache;

use Codemonster\Annabel\Cache\ArrayCache;
use Codemonster\Annabel\Cache\FileCache;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CacheTest extends TestCase
{
    public function test_array_cache_implements_psr16_contract()
    {
        $cache = new ArrayCache();

        $this->assertInstanceOf(CacheInterface::class, $cache);
        $this->assertTrue($cache->set('name', 'annabel'));
        $this->assertTrue($cache->has('name'));
        $this->assertSame('annabel', $cache->get('name'));
        $this->assertTrue($cache->delete('name'));
        $this->assertSame('fallback', $cache->get('name', 'fallback'));
    }

    public function test_array_cache_supports_multiple_operations()
    {
        $cache = new ArrayCache();

        $this->assertTrue($cache->setMultiple(['a' => 1, 'b' => 2]));
        $this->assertSame(['a' => 1, 'b' => 2, 'c' => null], $cache->getMultiple(['a', 'b', 'c']));
        $this->assertTrue($cache->deleteMultiple(['a', 'b']));
        $this->assertFalse($cache->has('a'));
    }

    public function test_array_cache_expires_items()
    {
        $cache = new ArrayCache();

        $cache->set('short', 'value', 0);

        $this->assertFalse($cache->has('short'));
        $this->assertSame('missing', $cache->get('short', 'missing'));
    }

    public function test_invalid_key_throws_psr_exception()
    {
        $cache = new ArrayCache();

        $this->expectException(InvalidArgumentException::class);

        $cache->get('bad/key');
    }

    public function test_file_cache_persists_values()
    {
        $path = sys_get_temp_dir() . '/annabel-cache-' . bin2hex(random_bytes(6));
        $cache = new FileCache($path);

        try {
            $this->assertTrue($cache->set('name', 'annabel'));
            $this->assertSame('annabel', (new FileCache($path))->get('name'));
            $this->assertTrue($cache->clear());
            $this->assertFalse($cache->has('name'));
        } finally {
            if (is_dir($path)) {
                @rmdir($path);
            }
        }
    }
}
