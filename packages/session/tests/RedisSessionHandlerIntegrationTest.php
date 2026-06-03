<?php

use Codemonster\Session\Handlers\RedisSessionHandler;
use Codemonster\Session\Store;
use PHPUnit\Framework\TestCase;

class RedisSessionHandlerIntegrationTest extends TestCase
{
    public function testReadWriteAndDestroyWithRedis(): void
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('ext-redis is required for Redis integration tests.');
        }
        if (!getenv('REDIS_TESTS')) {
            $this->markTestSkipped('Set REDIS_TESTS=1 to run Redis integration tests.');
        }

        $host = getenv('REDIS_HOST') ?: '127.0.0.1';
        $port = (int) (getenv('REDIS_PORT') ?: 6379);

        $redis = new Redis();
        $this->assertTrue($redis->connect($host, $port));

        $handler = new RedisSessionHandler($redis, 'sess_', 0);
        $id = bin2hex(random_bytes(16));

        $store = new Store($handler, $id);
        $store->start();
        $store->put('token', 'abc');

        $store2 = new Store($handler, $id);
        $store2->start();

        $this->assertSame('abc', $store2->get('token'));

        $store2->destroy();
    }
}
