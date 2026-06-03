<?php

namespace Codemonster\Session\Handlers;

use Redis;
use RedisSentinel;
use SessionHandlerInterface;

class RedisSentinelSessionHandler implements SessionHandlerInterface
{
    protected Redis $redis;
    protected string $prefix;
    protected int $ttl;
    protected int $retries;
    protected int $retryDelayMs;

    public function __construct(
        RedisSentinel $sentinel,
        string $service,
        string $prefix = 'sess_',
        int $ttl = 0,
        ?string $password = null,
        ?int $database = null,
        int $retries = 1,
        int $retryDelayMs = 50
    ) {
        $address = $sentinel->getMasterAddrByName($service);
        if (!is_array($address) || count($address) < 2) {
            throw new \RuntimeException("Unable to resolve Redis master for service: {$service}");
        }

        $host = (string) $address[0];
        $port = (int) $address[1];

        $redis = new Redis();
        if (!$redis->connect($host, $port)) {
            throw new \RuntimeException("Unable to connect to Redis master at {$host}:{$port}");
        }

        if ($password !== null && $password !== '') {
            if (!$redis->auth($password)) {
                throw new \RuntimeException('Redis authentication failed.');
            }
        }

        if ($database !== null) {
            $redis->select($database);
        }

        $this->redis = $redis;
        $this->prefix = $prefix;
        $this->ttl = $ttl;
        $this->retries = max(0, $retries);
        $this->retryDelayMs = max(0, $retryDelayMs);
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        $value = $this->retry(function () use ($id) {
            return $this->redis->get($this->prefix . $id);
        });

        return is_string($value) ? $value : '';
    }

    public function write(string $id, string $data): bool
    {
        $key = $this->prefix . $id;

        if ($this->ttl > 0) {
            $result = $this->retry(function () use ($key, $data) {
                return $this->redis->setex($key, $this->ttl, $data);
            });
        } else {
            $result = $this->retry(function () use ($key, $data) {
                return $this->redis->set($key, $data);
            });
        }

        return $result === true || is_string($result);
    }

    public function destroy(string $id): bool
    {
        $this->retry(function () use ($id) {
            return $this->redis->del($this->prefix . $id);
        });

        return true;
    }

    public function gc(int $max_lifetime): int|false
    {
        return 0;
    }

    private function retry(callable $callback): mixed
    {
        $attempt = 0;
        $lastException = null;

        while ($attempt <= $this->retries) {
            try {
                return $callback();
            } catch (\RedisException $exception) {
                $lastException = $exception;
                if ($attempt >= $this->retries) {
                    throw $exception;
                }
                if ($this->retryDelayMs > 0) {
                    usleep($this->retryDelayMs * 1000);
                }
            }

            $attempt++;
        }

        if ($lastException instanceof \Throwable) {
            throw $lastException;
        }

        return null;
    }
}
