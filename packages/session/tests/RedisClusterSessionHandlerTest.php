<?php

use Codemonster\Session\Handlers\RedisClusterSessionHandler;
use PHPUnit\Framework\TestCase;

if (!class_exists('RedisCluster')) {
    /** @psalm-suppress UndefinedThisPropertyFetch */
    class RedisCluster
    {
        /** @var array<string, mixed> */
        public array $store = [];
        /** @var array<int, array<int, mixed>> */
        public array $calls = [];

        public function get(string $key)
        {
            $this->calls[] = ['get', $key];
            return $this->store[$key] ?? null;
        }

        public function set(string $key, mixed $value, mixed $options = null): RedisCluster|string|bool
        {
            $this->calls[] = ['set', $key, $value];
            $this->store[$key] = $value;
            return true;
        }

        public function setex(string $key, int $seconds, string $value)
        {
            $this->calls[] = ['setex', $key, $seconds, $value];
            $this->store[$key] = $value;
            return true;
        }

        public function del(string $key)
        {
            $this->calls[] = ['del', $key];
            unset($this->store[$key]);
            return 1;
        }
    }
}

class RedisClusterSessionHandlerTest extends TestCase
{
    public function testReadWriteAndDestroy(): void
    {
        if (!class_exists('RedisCluster')) {
            $this->markTestSkipped('RedisCluster class is not available.');
        }
        if (!getenv('REDIS_CLUSTER_TESTS')) {
            $this->markTestSkipped('Set REDIS_CLUSTER_TESTS=1 to run RedisCluster integration tests.');
        }

        $seeds = getenv('REDIS_CLUSTER_SEEDS') ?: '127.0.0.1:7000,127.0.0.1:7001,127.0.0.1:7002';
        $seedList = array_filter(array_map('trim', explode(',', $seeds)));
        if ($seedList === []) {
            $this->markTestSkipped('REDIS_CLUSTER_SEEDS is empty.');
        }

        $cluster = new RedisCluster(null, $seedList);
        $handler = new RedisClusterSessionHandler($cluster, 'sess_', 0);

        $this->assertSame('', $handler->read('abc'));

        $this->assertTrue($handler->write('abc', 'value'));
        $this->assertSame('value', $handler->read('abc'));

        $this->assertTrue($handler->destroy('abc'));
        $this->assertSame('', $handler->read('abc'));
    }

    public function testWriteUsesTtlWhenConfigured(): void
    {
        if (!class_exists('RedisCluster')) {
            $this->markTestSkipped('RedisCluster class is not available.');
        }
        if (!getenv('REDIS_CLUSTER_TESTS')) {
            $this->markTestSkipped('Set REDIS_CLUSTER_TESTS=1 to run RedisCluster integration tests.');
        }

        $seeds = getenv('REDIS_CLUSTER_SEEDS') ?: '127.0.0.1:7000,127.0.0.1:7001,127.0.0.1:7002';
        $seedList = array_filter(array_map('trim', explode(',', $seeds)));
        if ($seedList === []) {
            $this->markTestSkipped('REDIS_CLUSTER_SEEDS is empty.');
        }

        $cluster = new RedisCluster(null, $seedList);
        $handler = new RedisClusterSessionHandler($cluster, 'sess_', 60);

        $handler->write('abc', 'value');

        if (property_exists($cluster, 'calls')) {
            $this->assertSame(['setex', 'sess_abc', 60, 'value'], $cluster->calls[0] ?? []);
        } elseif (method_exists($cluster, 'ttl')) {
            $ttl = $cluster->ttl('sess_abc');
            $this->assertGreaterThan(0, $ttl);
            $this->assertLessThanOrEqual(60, $ttl);
        }
    }

    public function testRetryOnFailure(): void
    {
        if (!class_exists('RedisCluster')) {
            $this->markTestSkipped('RedisCluster class is not available.');
        }
        if (!getenv('REDIS_CLUSTER_TESTS')) {
            $this->markTestSkipped('Set REDIS_CLUSTER_TESTS=1 to run RedisCluster integration tests.');
        }

        $seeds = getenv('REDIS_CLUSTER_SEEDS') ?: '127.0.0.1:7000,127.0.0.1:7001,127.0.0.1:7002';
        $seedList = array_filter(array_map('trim', explode(',', $seeds)));
        if ($seedList === []) {
            $this->markTestSkipped('REDIS_CLUSTER_SEEDS is empty.');
        }

        $cluster = new class(null, $seedList) extends RedisCluster {
            private int $tries = 0;
            public function set(string $key, mixed $value, mixed $options = null): RedisCluster|string|bool
            {
                $this->tries++;
                if ($this->tries === 1) {
                    throw new \RedisException('fail');
                }
                return parent::set($key, $value, $options);
            }
        };

        $handler = new RedisClusterSessionHandler($cluster, 'sess_', 0, 1, 0);
        $this->assertTrue($handler->write('abc', 'value'));
    }
}
