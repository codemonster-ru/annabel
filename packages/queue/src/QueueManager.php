<?php

namespace Codemonster\Queue;

use Codemonster\Database\Contracts\ConnectionInterface;
use Codemonster\Queue\Contracts\FailedJobRepositoryInterface;
use Codemonster\Queue\Contracts\QueueInterface;

class QueueManager
{
    /** @var array<string, mixed> */
    protected array $config;
    /** @var array<string, QueueInterface> */
    protected array $connections = [];
    /** @var array<string, FailedJobRepositoryInterface> */
    protected array $failedJobRepositories = [];
    /** @var \Closure():ConnectionInterface|null */
    protected ?\Closure $databaseConnectionResolver;

    /**
     * @param array<string, mixed> $config
     * @param \Closure():ConnectionInterface|null $databaseConnectionResolver
     */
    public function __construct(array $config, ?\Closure $databaseConnectionResolver = null)
    {
        $this->config = $config;
        $this->databaseConnectionResolver = $databaseConnectionResolver;
    }

    public function defaultConnection(): string
    {
        $default = $this->config['default'] ?? 'sync';

        return is_string($default) && $default !== '' ? $default : 'sync';
    }

    public function connection(?string $name = null): QueueInterface
    {
        $name ??= $this->defaultConnection();

        if ($name === '') {
            throw new QueueException('Queue connection name cannot be empty.');
        }

        return $this->connections[$name] ??= $this->createConnection($name);
    }

    public function setConnection(string $name, QueueInterface $queue): void
    {
        if ($name === '') {
            throw new QueueException('Queue connection name cannot be empty.');
        }

        $this->connections[$name] = $queue;
    }

    public function failedJobs(?string $name = null): FailedJobRepositoryInterface
    {
        $name ??= $this->defaultConnection();
        $config = $this->connectionConfig($name);
        $driver = $config['driver'] ?? $name;

        if ($driver === 'redis') {
            $queue = $this->connection($name);
            if (!$queue instanceof RedisQueue) {
                throw new QueueException("Queue connection [{$name}] does not support failed jobs.");
            }

            return $this->failedJobRepositories[$name] ??= new RedisFailedJobRepository(
                $this->redisClient($config),
                $queue,
            );
        }

        if ($driver !== 'database') {
            throw new QueueException("Queue connection [{$name}] does not support failed jobs.");
        }

        return $this->failedJobRepositories[$name] ??= new FailedJobRepository(
            $this->databaseConnection($config),
            $this->stringConfig($config, 'table', 'jobs'),
            $this->stringConfig($config, 'failed_table', 'failed_jobs'),
            $this->intConfig($config, 'max_attempts', 3),
        );
    }

    /**
     * @return list<string>
     */
    public function connections(): array
    {
        $connections = $this->config['connections'] ?? [];

        if (!is_array($connections)) {
            return [];
        }

        return array_values(array_filter(array_keys($connections), 'is_string'));
    }

    protected function createConnection(string $name): QueueInterface
    {
        $config = $this->connectionConfig($name);
        $driver = $config['driver'] ?? $name;
        $driver = is_string($driver) && $driver !== '' ? $driver : $name;

        if ($driver === 'sync') {
            return new SyncQueue($name);
        }

        if ($driver === 'database') {
            return new DatabaseQueue(
                $this->databaseConnection($config),
                $name,
                $this->stringConfig($config, 'table', 'jobs'),
                $this->stringConfig($config, 'failed_table', 'failed_jobs'),
                $this->intConfig($config, 'retry_after', 60),
                $this->intConfig($config, 'max_attempts', 3),
            );
        }

        if ($driver === 'redis') {
            return new RedisQueue(
                $this->redisClient($config),
                $name,
                $this->stringConfig($config, 'prefix', 'queue:'),
                $this->intConfig($config, 'retry_after', 60),
                $this->intConfig($config, 'max_attempts', 3),
            );
        }

        throw new QueueException("Unsupported queue driver [{$driver}].");
    }

    /**
     * @param array<string, mixed> $config
     */
    protected function databaseConnection(array $config): ConnectionInterface
    {
        $connection = $config['connection'] ?? null;

        if ($connection instanceof ConnectionInterface) {
            return $connection;
        }

        if ($this->databaseConnectionResolver) {
            return ($this->databaseConnectionResolver)();
        }

        throw new QueueException('Database queue driver requires a database connection instance.');
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
            throw new QueueException('Redis queue driver requires the PHP Redis extension or a configured client object.');
        }

        $redis = new \Redis();
        $connected = $redis->connect(
            $this->stringConfig($config, 'host', '127.0.0.1'),
            $this->intConfig($config, 'port', 6379),
            $this->floatConfig($config, 'timeout', 2.0),
        );

        if (!$connected) {
            throw new QueueException('Unable to connect to the Redis queue server.');
        }

        $password = $config['password'] ?? null;
        if (is_string($password) && $password !== '' && !$redis->auth($password)) {
            throw new QueueException('Unable to authenticate with the Redis queue server.');
        }

        $database = $this->intConfig($config, 'database', 0);
        if ($database !== 0 && !$redis->select($database)) {
            throw new QueueException('Unable to select the Redis queue database.');
        }

        return $redis;
    }

    /**
     * @return array<string, mixed>
     */
    protected function connectionConfig(string $name): array
    {
        $connections = $this->config['connections'] ?? null;

        if (!is_array($connections) || !isset($connections[$name]) || !is_array($connections[$name])) {
            throw new QueueException("Queue connection [{$name}] is not configured.");
        }

        $config = [];
        foreach ($connections[$name] as $key => $value) {
            if (is_string($key)) {
                $config[$key] = $value;
            }
        }

        return $config;
    }

    /**
     * @param array<string, mixed> $config
     */
    protected function stringConfig(array $config, string $key, string $default): string
    {
        $value = $config[$key] ?? $default;

        return is_string($value) && $value !== '' ? $value : $default;
    }

    /**
     * @param array<string, mixed> $config
     */
    protected function intConfig(array $config, string $key, int $default): int
    {
        $value = $config[$key] ?? $default;

        return is_int($value) ? $value : $default;
    }

    /**
     * @param array<string, mixed> $config
     */
    protected function floatConfig(array $config, string $key, float $default): float
    {
        $value = $config[$key] ?? $default;

        return is_int($value) || is_float($value) ? (float) $value : $default;
    }
}
