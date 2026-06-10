<?php

namespace Codemonster\Cache;

use Codemonster\Cache\Contracts\CacheStoreInterface;
use Codemonster\Cache\Exceptions\CacheException;

class CacheManager
{
    /** @var array<string, mixed> */
    protected array $config;
    /** @var array<string, CacheStoreInterface> */
    protected array $stores = [];

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function defaultStore(): string
    {
        $default = $this->config['default'] ?? 'array';

        return is_string($default) && $default !== '' ? $default : 'array';
    }

    public function store(?string $name = null): CacheStoreInterface
    {
        $name ??= $this->defaultStore();

        if ($name === '') {
            throw new CacheException('Cache store name cannot be empty.');
        }

        return $this->stores[$name] ??= $this->createStore($name);
    }

    public function setStore(string $name, CacheStoreInterface $store): void
    {
        if ($name === '') {
            throw new CacheException('Cache store name cannot be empty.');
        }

        $this->stores[$name] = $store;
    }

    /**
     * @return list<string>
     */
    public function stores(): array
    {
        $stores = $this->config['stores'] ?? [];

        if (!is_array($stores)) {
            return [];
        }

        return array_values(array_filter(array_keys($stores), 'is_string'));
    }

    protected function createStore(string $name): CacheStoreInterface
    {
        $config = $this->storeConfig($name);
        $driver = $config['driver'] ?? $name;
        $driver = is_string($driver) && $driver !== '' ? $driver : $name;

        if ($driver === 'array') {
            return new ArrayCache();
        }

        if ($driver === 'file') {
            $path = $config['path'] ?? null;
            if (!is_string($path) || $path === '') {
                throw new CacheException("Cache store [{$name}] requires a path.");
            }

            return new FileCache($path);
        }

        if ($driver === 'redis') {
            return new RedisCache(
                $this->redisClient($config),
                $this->stringConfig($config, 'prefix', 'cache:'),
            );
        }

        throw new CacheException("Unsupported cache driver [{$driver}].");
    }

    /**
     * @param array<string, mixed> $config
     */
    protected function redisClient(array $config): object
    {
        $client = $config['client'] ?? null;

        if (is_object($client)) {
            return $client;
        }

        if (!class_exists(\Redis::class)) {
            throw new CacheException('Redis cache driver requires the PHP Redis extension or a configured client object.');
        }

        $redis = new \Redis();
        $connected = $redis->connect(
            $this->stringConfig($config, 'host', '127.0.0.1'),
            $this->intConfig($config, 'port', 6379),
            $this->floatConfig($config, 'timeout', 2.0),
        );

        if (!$connected) {
            throw new CacheException('Unable to connect to the Redis cache server.');
        }

        $password = $config['password'] ?? null;
        if (is_string($password) && $password !== '' && !$redis->auth($password)) {
            throw new CacheException('Unable to authenticate with the Redis cache server.');
        }

        $database = $this->intConfig($config, 'database', 0);
        if ($database !== 0 && !$redis->select($database)) {
            throw new CacheException('Unable to select the Redis cache database.');
        }

        return $redis;
    }

    /**
     * @return array<string, mixed>
     */
    protected function storeConfig(string $name): array
    {
        $stores = $this->config['stores'] ?? null;

        if (!is_array($stores) || !isset($stores[$name]) || !is_array($stores[$name])) {
            throw new CacheException("Cache store [{$name}] is not configured.");
        }

        $config = [];
        foreach ($stores[$name] as $key => $value) {
            if (is_string($key)) {
                $config[$key] = $value;
            }
        }

        return $config;
    }

    /**
     * @param array<string, mixed> $config
     */
    private function stringConfig(array $config, string $key, string $default): string
    {
        $value = $config[$key] ?? $default;

        return is_string($value) && $value !== '' ? $value : $default;
    }

    /**
     * @param array<string, mixed> $config
     */
    private function intConfig(array $config, string $key, int $default): int
    {
        $value = $config[$key] ?? $default;

        return is_int($value) ? $value : $default;
    }

    /**
     * @param array<string, mixed> $config
     */
    private function floatConfig(array $config, string $key, float $default): float
    {
        $value = $config[$key] ?? $default;

        return is_int($value) || is_float($value) ? (float) $value : $default;
    }
}
