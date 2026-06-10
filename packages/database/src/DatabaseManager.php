<?php

namespace Codemonster\Database;

use Codemonster\Database\Contracts\ConnectionInterface;
use InvalidArgumentException;

class DatabaseManager
{
    /**
     * @var array<string, ConnectionInterface>
     */
    protected array $connections = [];

    /**
     * @var array<string, mixed>
     */
    protected array $config;

    /** @param array<string, mixed> $config */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function connection(?string $name = null): ConnectionInterface
    {
        $name ??= $this->getDefaultConnectionName();

        if (!isset($this->connections[$name])) {
            $connections = $this->config['connections'] ?? null;
            $connectionConfig = is_array($connections) ? ($connections[$name] ?? null) : null;

            if (!is_array($connectionConfig)) {
                throw new InvalidArgumentException(
                    sprintf('Database connection "%s" is not configured.', $name),
                );
            }

            foreach ($connectionConfig as $key => $_) {
                if (!is_string($key)) {
                    throw new InvalidArgumentException('Database connection config keys must be strings.');
                }
            }

            /** @var array<string, mixed> $connectionConfig */
            $this->connections[$name] = new Connection($connectionConfig);
        }

        return $this->connections[$name];
    }

    public function getDefaultConnectionName(): string
    {
        if (!isset($this->config['default'])) {
            throw new InvalidArgumentException('Database default connection name is not configured.');
        }

        $name = $this->config['default'];
        if (!is_string($name) || $name === '') {
            throw new InvalidArgumentException('Database default connection name must be a non-empty string.');
        }

        return $name;
    }

    public function setDefaultConnectionName(string $name): void
    {
        $this->config['default'] = $name;
    }

    /**
     * Proxy calls to the default connection.
     *
     * Example:
     * $db->table('users')->get();
     * $db->select('SELECT 1');
     */
    /** @param list<mixed> $arguments */
    public function __call(string $method, array $arguments): mixed
    {
        return $this->connection()->{$method}(...$arguments);
    }
}
