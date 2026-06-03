<?php

namespace Codemonster\Annabel\Database;

use Codemonster\Database\Contracts\ConnectionInterface;
use Codemonster\Database\Contracts\QueryBuilderInterface;
use Codemonster\Database\Schema\Schema;

/**
 * Connection proxy that resolves the real connection only when first used.
 */
class LazyConnection implements ConnectionInterface
{
    protected \Closure $resolver;
    protected ?ConnectionInterface $resolved = null;

    public function __construct(\Closure $resolver)
    {
        $this->resolver = $resolver;
    }

    protected function connection(): ConnectionInterface
    {
        if (!$this->resolved) {
            $this->resolved = ($this->resolver)();
        }

        return $this->resolved;
    }

    public function select(string $query, array $params = []): array
    {
        return $this->connection()->select($query, $params);
    }

    public function selectOne(string $query, array $params = []): ?array
    {
        return $this->connection()->selectOne($query, $params);
    }

    public function insert(string $query, array $params = []): bool
    {
        return $this->connection()->insert($query, $params);
    }

    public function update(string $query, array $params = []): int
    {
        return $this->connection()->update($query, $params);
    }

    public function delete(string $query, array $params = []): int
    {
        return $this->connection()->delete($query, $params);
    }

    public function statement(string $query, array $params = []): bool
    {
        return $this->connection()->statement($query, $params);
    }

    public function table(string $table): QueryBuilderInterface
    {
        return $this->connection()->table($table);
    }

    public function beginTransaction(): bool
    {
        return $this->connection()->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->connection()->commit();
    }

    public function rollBack(): bool
    {
        return $this->connection()->rollBack();
    }

    public function transaction(callable $callback): mixed
    {
        return $this->connection()->transaction($callback);
    }

    public function getPdo(): \PDO
    {
        return $this->connection()->getPdo();
    }

    public function schema(): Schema
    {
        return $this->connection()->schema();
    }
}
