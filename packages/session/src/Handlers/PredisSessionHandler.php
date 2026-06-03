<?php

namespace Codemonster\Session\Handlers;

use Predis\Client;
use SessionHandlerInterface;

class PredisSessionHandler implements SessionHandlerInterface
{
    protected Client $client;
    protected string $prefix;
    protected int $ttl;
    protected int $retries;
    protected int $retryDelayMs;

    public function __construct(
        Client $client,
        string $prefix = 'sess_',
        int $ttl = 0,
        int $retries = 1,
        int $retryDelayMs = 50
    )
    {
        $this->client = $client;
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
            return $this->client->get($this->prefix . $id);
        });

        return is_string($value) ? $value : '';
    }

    public function write(string $id, string $data): bool
    {
        $key = $this->prefix . $id;

        if ($this->ttl > 0) {
            $result = $this->retry(function () use ($key, $data) {
                return $this->client->setex($key, $this->ttl, $data);
            });
        }

        if ($this->ttl <= 0) {
            $result = $this->retry(function () use ($key, $data) {
                return $this->client->set($key, $data);
            });
        }

        return is_string($result) || $result === true;
    }

    public function destroy(string $id): bool
    {
        $this->retry(function () use ($id) {
            return $this->client->del([$this->prefix . $id]);
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
