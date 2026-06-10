<?php

namespace Codemonster\Security\Tests\RateLimiting\Storage;

use Codemonster\Security\RateLimiting\Storage\RedisThrottleStorage;
use PHPUnit\Framework\TestCase;

class RedisThrottleStorageTest extends TestCase
{
    public function testRedisIncrementIsAtomic(): void
    {
        if (!class_exists('Redis')) {
            $this->markTestSkipped('ext-redis is not installed.');
        }

        $host = getenv('REDIS_HOST') ?: null;

        if (!$host) {
            $this->markTestSkipped('Redis env is not configured.');
        }

        $port = (int) (getenv('REDIS_PORT') ?: 6379);
        $password = getenv('REDIS_PASSWORD') ?: null;
        $db = getenv('REDIS_DB');

        $redisClass = 'Redis';
        $redis = new $redisClass();
        $redis->connect($host, $port, 1.0);

        if (is_string($password) && $password !== '') {
            $redis->auth($password);
        }

        if ($db !== false && $db !== null && $db !== '') {
            $redis->select((int) $db);
        }

        $prefix = 'throttle_test:' . bin2hex(random_bytes(4)) . ':';
        $storage = new RedisThrottleStorage($redis, $prefix);
        $key = 'login:' . bin2hex(random_bytes(4));

        $now = time();
        $first = $storage->increment($key, 5, $now);
        $this->assertSame(1, $first['attempts']);
        $this->assertGreaterThanOrEqual($now, $first['expires_at']);

        $second = $storage->increment($key, 5, $now + 1);
        $this->assertSame(2, $second['attempts']);
        $this->assertGreaterThanOrEqual($now, $second['expires_at']);

        $redis->del($prefix . $key);
    }
}
