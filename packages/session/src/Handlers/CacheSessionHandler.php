<?php

namespace Codemonster\Session\Handlers;

use Psr\SimpleCache\CacheInterface;
use SessionHandlerInterface;

class CacheSessionHandler implements SessionHandlerInterface
{
    protected CacheInterface $cache;
    protected string $prefix;
    protected int $ttl;
    protected int $retries;
    protected int $retryDelayMs;

    public function __construct(
        CacheInterface $cache,
        string $prefix = 'sess_',
        int $ttl = 0,
        int $retries = 1,
        int $retryDelayMs = 50
    )
    {
        $this->cache = $cache;
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
            return $this->cache->get($this->prefix . $id);
        });

        if ($value === null) {
            return '';
        }

        if (!is_string($value)) {
            throw new \RuntimeException('Invalid cache payload type.');
        }

        return $value;
    }

    public function write(string $id, string $data): bool
    {
        if ($this->ttl > 0) {
            return $this->retry(function () use ($id, $data) {
                return $this->cache->set($this->prefix . $id, $data, $this->ttl);
            }) === true;
        }

        return $this->retry(function () use ($id, $data) {
            return $this->cache->set($this->prefix . $id, $data);
        }) === true;
    }

    public function destroy(string $id): bool
    {
        $this->retry(function () use ($id) {
            return $this->cache->delete($this->prefix . $id);
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
            } catch (\Throwable $exception) {
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
