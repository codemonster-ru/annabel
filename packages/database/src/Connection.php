<?php

namespace Codemonster\Database;

use Codemonster\Database\Contracts\ConnectionInterface;
use Codemonster\Database\Contracts\QueryBuilderInterface;
use Codemonster\Database\Exceptions\QueryException;
use Codemonster\Database\Query\QueryBuilder;
use Codemonster\Database\Schema\Schema;
use InvalidArgumentException;
use PDO;
use PDOException;
use PDOStatement;
use Throwable;

class Connection implements ConnectionInterface
{
    /** @var PDO */
    protected $pdo;

    /** @param array<string, mixed> $config */
    public function __construct(array $config)
    {
        $driver = $config['driver'] ?? 'mysql';
        if (!is_string($driver)) {
            throw new InvalidArgumentException('Database driver must be a string.');
        }

        match ($driver) {
            'mysql' => $this->connectMySql($config),
            'sqlite' => $this->connectSqlite($config),
            default => throw new InvalidArgumentException("Unsupported driver [$driver].")
        };
    }

    /** @param array<string, mixed> $config */
    protected function connectMySql(array $config): void
    {
        $defaults = [
            'host' => '127.0.0.1',
            'port' => 3306,
            'charset' => 'utf8mb4',
            'options' => [],
        ];

        $config = array_replace($defaults, $config);

        foreach (['database', 'username', 'password'] as $key) {
            if (!array_key_exists($key, $config)) {
                throw new InvalidArgumentException(
                    sprintf('Database connection config is missing required key: "%s".', $key),
                );
            }
        }

        $host = self::stringConfig($config, 'host');
        $port = $config['port'];
        $database = self::stringConfig($config, 'database');
        $charset = self::stringConfig($config, 'charset');
        $username = $config['username'];
        $password = $config['password'];

        if ((!is_string($port) && !is_int($port))
            || ($username !== null && !is_string($username))
            || ($password !== null && !is_string($password))) {
            throw new InvalidArgumentException('Invalid MySQL connection config.');
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $host,
            $port,
            $database,
            $charset,
        );

        $options = $config['options'] ?? [];
        if (!is_array($options)) {
            throw new InvalidArgumentException('PDO options must be an array.');
        }
        $options[PDO::ATTR_ERRMODE] ??= PDO::ERRMODE_EXCEPTION;
        $options[PDO::ATTR_DEFAULT_FETCH_MODE] ??= PDO::FETCH_ASSOC;

        try {
            $this->pdo = new PDO(
                $dsn,
                $username,
                $password,
                $options,
            );
        } catch (PDOException $e) {
            throw new QueryException($e->getMessage(), $dsn, [], (int) $e->getCode(), $e);
        }
    }

    /** @param array<string, mixed> $config */
    protected function connectSqlite(array $config): void
    {
        if (!isset($config['database'])) {
            throw new InvalidArgumentException('SQLite config must contain "database".');
        }

        $dsn = 'sqlite:' . self::stringConfig($config, 'database');

        try {
            $this->pdo = new PDO($dsn);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new QueryException($e->getMessage(), $dsn, [], (int) $e->getCode(), $e);
        }
    }

    /**
     * @param array<int|string, mixed> $params
     * @return list<array<string, mixed>>
     */
    public function select(string $query, array $params = []): array
    {
        $rows = $this->run($query, $params)->fetchAll();

        $result = [];
        foreach ($rows as $row) {
            if (is_array($row)) {
                $result[] = self::normalizeRow($row);
            }
        }

        return $result;
    }

    /**
     * @param array<int|string, mixed> $params
     * @return array<string, mixed>|null
     */
    public function selectOne(string $query, array $params = []): ?array
    {
        $result = $this->run($query, $params)->fetch();

        return is_array($result) ? self::normalizeRow($result) : null;
    }

    /** @param array<string, mixed> $config */
    private static function stringConfig(array $config, string $key): string
    {
        $value = $config[$key] ?? null;
        if (!is_string($value)) {
            throw new InvalidArgumentException("Database config [{$key}] must be a string.");
        }

        return $value;
    }

    /**
     * @param array<mixed, mixed> $row
     * @return array<string, mixed>
     */
    private static function normalizeRow(array $row): array
    {
        $normalized = [];
        foreach ($row as $key => $value) {
            if (is_string($key)) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }

    /** @param array<int|string, mixed> $params */
    public function insert(string $query, array $params = []): bool
    {
        return $this->statement($query, $params);
    }

    /** @param array<int|string, mixed> $params */
    public function update(string $query, array $params = []): int
    {
        return $this->run($query, $params)->rowCount();
    }

    /** @param array<int|string, mixed> $params */
    public function delete(string $query, array $params = []): int
    {
        return $this->run($query, $params)->rowCount();
    }

    /** @param array<int|string, mixed> $params */
    public function statement(string $query, array $params = []): bool
    {
        $this->run($query, $params);

        return true;
    }

    /** @param array<int|string, mixed> $params */
    protected function run(string $query, array $params = []): PDOStatement
    {
        try {
            $stmt = $this->pdo->prepare($query);

            if (!$stmt) {
                throw new QueryException('Failed to prepare SQL statement.', $query, $params);
            }

            $stmt->execute($params);

            return $stmt;
        } catch (PDOException $e) {
            throw new QueryException($e->getMessage(), $query, $params, (int) $e->getCode(), $e);
        }
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    public function table(string $table): QueryBuilderInterface
    {
        return new QueryBuilder($this, $table);
    }

    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }

    /**
     * @template T
     * @param callable(self):T $callback
     * @return T
     */
    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();

        try {
            $result = $callback($this);

            // Some drivers (e.g., MySQL DDL) may auto-commit and end the transaction.
            // Commit only when a transaction is still open to avoid "no active transaction" errors.
            if ($this->pdo->inTransaction()) {
                $this->commit();
            }

            return $result;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->rollBack();
            }

            throw $e;
        }
    }

    public function schema(): Schema
    {
        return Schema::forConnection($this);
    }
}
