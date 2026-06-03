<?php

namespace Codemonster\Database;

use Codemonster\Database\Contracts\ConnectionInterface;
use Codemonster\Database\Contracts\QueryBuilderInterface;
use Codemonster\Database\Exceptions\QueryException;
use Codemonster\Database\Query\QueryBuilder;
use Codemonster\Database\Schema\Schema;
use PDO;
use PDOException;
use PDOStatement;
use InvalidArgumentException;
use Throwable;

class Connection implements ConnectionInterface
{
    /**
     * @var \PDO|\stdClass|object
     */
    protected $pdo;

    public function __construct(array $config)
    {
        $driver = $config['driver'] ?? 'mysql';

        match ($driver) {
            'mysql'  => $this->connectMySql($config),
            'sqlite' => $this->connectSqlite($config),
            default  => throw new InvalidArgumentException("Unsupported driver [$driver].")
        };
    }

    protected function connectMySql(array $config): void
    {
        $defaults = [
            'host'    => '127.0.0.1',
            'port'    => 3306,
            'charset' => 'utf8mb4',
            'options' => [],
        ];

        $config = array_replace($defaults, $config);

        foreach (['database', 'username', 'password'] as $key) {
            if (!array_key_exists($key, $config)) {
                throw new InvalidArgumentException(
                    sprintf('Database connection config is missing required key: "%s".', $key)
                );
            }
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );

        $options = $config['options'] ?? [];
        $options[PDO::ATTR_ERRMODE] ??= PDO::ERRMODE_EXCEPTION;
        $options[PDO::ATTR_DEFAULT_FETCH_MODE] ??= PDO::FETCH_ASSOC;

        try {
            $this->pdo = new PDO(
                $dsn,
                $config['username'],
                $config['password'],
                $options
            );
        } catch (PDOException $e) {
            throw new QueryException($e->getMessage(), $dsn, [], (int)$e->getCode(), $e);
        }
    }

    protected function connectSqlite(array $config): void
    {
        if (!isset($config['database'])) {
            throw new InvalidArgumentException('SQLite config must contain "database".');
        }

        $dsn = 'sqlite:' . $config['database'];

        try {
            $this->pdo = new PDO($dsn);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new QueryException($e->getMessage(), $dsn, [], (int)$e->getCode(), $e);
        }
    }

    public function select(string $query, array $params = []): array
    {
        return $this->run($query, $params)->fetchAll();
    }

    public function selectOne(string $query, array $params = []): ?array
    {
        $result = $this->run($query, $params)->fetch();

        return $result !== false ? $result : null;
    }

    public function insert(string $query, array $params = []): bool
    {
        return $this->statement($query, $params);
    }

    public function update(string $query, array $params = []): int
    {
        return $this->run($query, $params)->rowCount();
    }

    public function delete(string $query, array $params = []): int
    {
        return $this->run($query, $params)->rowCount();
    }

    public function statement(string $query, array $params = []): bool
    {
        return $this->run($query, $params) !== false;
    }

    protected function run(string $query, array $params = []): PDOStatement
    {
        try {
            $stmt = $this->pdo->prepare($query);

            if (!$stmt) {
                throw new QueryException("Failed to prepare SQL statement.", $query, $params);
            }

            $stmt->execute($params);

            return $stmt;
        } catch (PDOException $e) {
            throw new QueryException($e->getMessage(), $query, $params, (int)$e->getCode(), $e);
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
