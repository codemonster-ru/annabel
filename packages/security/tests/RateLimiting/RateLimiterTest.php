<?php

namespace Codemonster\Security\Tests\RateLimiting;

use Codemonster\Security\RateLimiting\RateLimiter;
use Codemonster\Security\RateLimiting\Storage\SessionThrottleStorage;
use Codemonster\Session\Session;
use PHPUnit\Framework\TestCase;

class RateLimiterTest extends TestCase
{
    public function testTracksAttemptsAndExpiry(): void
    {
        Session::start('array');

        $now = 1000;
        $limiter = new RateLimiter(new SessionThrottleStorage(), function () use (&$now) {
            return $now;
        });

        $key = 'login:' . sha1('a');

        $this->assertFalse($limiter->tooManyAttempts($key, 2));
        $this->assertSame(1, $limiter->hit($key, 10));
        $this->assertSame(2, $limiter->hit($key, 10));
        $this->assertTrue($limiter->tooManyAttempts($key, 2));
        $this->assertSame(10, $limiter->availableIn($key));

        $now = 1011;

        $this->assertFalse($limiter->tooManyAttempts($key, 2));
        $this->assertSame(0, $limiter->availableIn($key));
    }

    public function testAtomicAttemptUsesSingleIncrement(): void
    {
        $storage = new ArrayAtomicStorage();
        $now = 2000;
        $limiter = new RateLimiter($storage, function () use (&$now) {
            return $now;
        });

        $result = $limiter->attempt('login', 2, 10);
        $this->assertSame(1, $result['attempts']);
        $this->assertFalse($result['limited']);

        $result = $limiter->attempt('login', 2, 10);
        $this->assertSame(2, $result['attempts']);
        $this->assertFalse($result['limited']);

        $result = $limiter->attempt('login', 2, 10);
        $this->assertSame(3, $result['attempts']);
        $this->assertTrue($result['limited']);
    }
}

class ArrayAtomicStorage implements \Codemonster\Security\RateLimiting\Storage\AtomicThrottleStorageInterface
{
    /** @var array<string, array{attempts: int, expires_at: int}> */
    private array $data = [];

    public function get(string $key): ?array
    {
        return $this->data[$key] ?? null;
    }

    public function put(string $key, array $value): void
    {
        $this->data[$key] = $value;
    }

    public function forget(string $key): void
    {
        unset($this->data[$key]);
    }

    public function increment(string $key, int $decaySeconds, int $now): array
    {
        $record = $this->data[$key] ?? null;

        if ($record === null || $record['expires_at'] <= $now) {
            $record = [
                'attempts' => 0,
                'expires_at' => $now + $decaySeconds,
            ];
        }

        $record['attempts']++;
        $this->data[$key] = $record;

        return $record;
    }
}
