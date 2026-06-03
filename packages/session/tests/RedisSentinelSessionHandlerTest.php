<?php

use Codemonster\Session\Handlers\RedisSentinelSessionHandler;
use PHPUnit\Framework\TestCase;

if (!class_exists('RedisSentinel')) {
    class RedisSentinel
    {
        public function getMasterAddrByName(string $service)
        {
            return ['127.0.0.1', 6379];
        }
    }
}

if (!class_exists('Redis')) {
    /** @psalm-suppress UndefinedThisPropertyFetch, UndefinedVariable */
    class Redis
    {
        /** @var array<string, mixed> */
        public array $store = [];
        /** @var array<int, array<int, mixed>> */
        public array $calls = [];

        public function connect(string $host, int $port): bool
        {
            $this->calls[] = ['connect', $host, $port];
            return true;
        }

        public function auth(string $password): bool
        {
            $this->calls[] = ['auth', $password];
            return true;
        }

        public function select(int $db): bool
        {
            $this->calls[] = ['select', $db];
            return true;
        }

        public function get(string $key): mixed
        {
            $this->calls[] = ['get', $key];
            return $this->store[$key] ?? null;
        }

        public function set(string $key, mixed $value, mixed $options = null): \Redis|string|bool
        {
            $this->calls[] = ['set', $key, $value];
            $this->store[$key] = $value;
            return true;
        }

        public function setex(string $key, int $seconds, string $value): \Redis|string|bool
        {
            $this->calls[] = ['setex', $key, $seconds, $value];
            $this->store[$key] = $value;
            return true;
        }

        public function del(string $key): int
        {
            $this->calls[] = ['del', $key];
            unset($this->store[$key]);
            return 1;
        }
    }
}

function createSentinel(string $host, int $port): RedisSentinel
{
    $ref = new ReflectionClass(RedisSentinel::class);
    $ctor = $ref->getConstructor();
    if ($ctor === null || $ctor->getNumberOfParameters() === 0) {
        return $ref->newInstance();
    }

    $params = $ctor->getParameters();
    if ($ctor->getNumberOfParameters() === 1) {
        $param = $params[0];
        if ($param->hasType()) {
            $type = $param->getType();
            if ($type instanceof ReflectionNamedType && $type->getName() === 'string') {
                return $ref->newInstanceArgs([$host]);
            }
        }

        $address = sprintf('%s:%d', $host, $port);
        return $ref->newInstanceArgs([[$address]]);
    }

    return $ref->newInstanceArgs([$host, $port]);
}

class RedisSentinelSessionHandlerTest extends TestCase
{
    public function testReadWriteAndDestroy(): void
    {
        if (!class_exists('RedisSentinel') || !class_exists('Redis')) {
            $this->markTestSkipped('RedisSentinel/Redis classes are not available.');
        }
        if (!getenv('REDIS_SENTINEL_TESTS')) {
            $this->markTestSkipped('Set REDIS_SENTINEL_TESTS=1 to run RedisSentinel integration tests.');
        }

        $host = getenv('REDIS_SENTINEL_HOST') ?: '127.0.0.1';
        $port = (int) (getenv('REDIS_SENTINEL_PORT') ?: 26379);
        $service = getenv('REDIS_SENTINEL_SERVICE') ?: 'mymaster';

        $sentinel = createSentinel($host, $port);
        $handler = new RedisSentinelSessionHandler($sentinel, $service, 'sess_', 0);

        $this->assertSame('', $handler->read('abc'));

        $this->assertTrue($handler->write('abc', 'value'));
        $this->assertSame('value', $handler->read('abc'));

        $this->assertTrue($handler->destroy('abc'));
        $this->assertSame('', $handler->read('abc'));
    }

    public function testWriteUsesTtlWhenConfigured(): void
    {
        if (!class_exists('RedisSentinel') || !class_exists('Redis')) {
            $this->markTestSkipped('RedisSentinel/Redis classes are not available.');
        }
        if (!getenv('REDIS_SENTINEL_TESTS')) {
            $this->markTestSkipped('Set REDIS_SENTINEL_TESTS=1 to run RedisSentinel integration tests.');
        }

        $host = getenv('REDIS_SENTINEL_HOST') ?: '127.0.0.1';
        $port = (int) (getenv('REDIS_SENTINEL_PORT') ?: 26379);
        $service = getenv('REDIS_SENTINEL_SERVICE') ?: 'mymaster';

        $sentinel = createSentinel($host, $port);
        $handler = new RedisSentinelSessionHandler($sentinel, $service, 'sess_', 60);

        $handler->write('abc', 'value');

        $redis = (new \ReflectionClass($handler))->getProperty('redis');
        $redis->setAccessible(true);
        $client = $redis->getValue($handler);

        if (property_exists($client, 'calls')) {
            $this->assertSame(['setex', 'sess_abc', 60, 'value'], $client->calls[1] ?? []);
        } else {
            $ttl = $client->ttl('sess_abc');
            $this->assertGreaterThan(0, $ttl);
            $this->assertLessThanOrEqual(60, $ttl);
        }
    }

    public function testRetryOnFailure(): void
    {
        if (!class_exists('RedisSentinel') || !class_exists('Redis')) {
            $this->markTestSkipped('RedisSentinel/Redis classes are not available.');
        }
        if (!getenv('REDIS_SENTINEL_TESTS')) {
            $this->markTestSkipped('Set REDIS_SENTINEL_TESTS=1 to run RedisSentinel integration tests.');
        }

        $host = getenv('REDIS_SENTINEL_HOST') ?: '127.0.0.1';
        $port = (int) (getenv('REDIS_SENTINEL_PORT') ?: 26379);
        $service = getenv('REDIS_SENTINEL_SERVICE') ?: 'mymaster';

        $sentinel = createSentinel($host, $port);

        $handler = new class($sentinel, $service, 'sess_', 0, null, null, 1, 0) extends RedisSentinelSessionHandler {
            public function __construct(
                RedisSentinel $sentinel,
                string $service,
                string $prefix,
                int $ttl,
                ?string $password,
                ?int $database,
                int $retries,
                int $retryDelayMs
            )
            {
                parent::__construct($sentinel, $service, $prefix, $ttl, $password, $database, $retries, $retryDelayMs);
                $redis = new class extends \Redis {
                    private int $tries = 0;
                    public function set(string $key, mixed $value, mixed $options = null): \Redis|string|bool
                    {
                        $this->tries++;
                        if ($this->tries === 1) {
                            throw new \RedisException('fail');
                        }
                        return true;
                    }
                };
                $ref = new \ReflectionProperty(RedisSentinelSessionHandler::class, 'redis');
                $ref->setAccessible(true);
                $ref->setValue($this, $redis);
            }
        };

        $this->assertTrue($handler->write('abc', 'value'));
    }
}
