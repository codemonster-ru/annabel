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

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function connection(?string $name = null): ConnectionInterface
    {
        $name ??= $this->getDefaultConnectionName();

        if (!isset($this->connections[$name])) {
            $connectionConfig = $this->config['connections'][$name] ?? null;

            if (!is_array($connectionConfig)) {
                throw new InvalidArgumentException(
                    sprintf('Database connection "%s" is not configured.', $name)
                );
            }

            $this->connections[$name] = new Connection($connectionConfig);
        }

        return $this->connections[$name];
    }

    public function getDefaultConnectionName(): string
    {
        if (!isset($this->config['default'])) {
            throw new InvalidArgumentException('Database default connection name is not configured.');
        }

        return $this->config['default'];
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
    public function __call(string $method, array $arguments)
    {
        return $this->connection()->{$method}(...$arguments);
    }
}
