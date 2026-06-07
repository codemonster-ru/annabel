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
    /** @var \Closure(): ConnectionInterface */
    protected \Closure $resolver;
    protected ?ConnectionInterface $resolved = null;

    /** @param \Closure(): ConnectionInterface $resolver */
    public function __construct(\Closure $resolver)
    {
        $this->resolver = $resolver;
    }

    protected function connection(): ConnectionInterface
    {
        if (!$this->resolved) {
            $connection = ($this->resolver)();

            if (!$connection instanceof ConnectionInterface) {
                throw new \RuntimeException('Lazy connection resolver must return ConnectionInterface.');
            }

            $this->resolved = $connection;
        }

        return $this->resolved;
    }

    /**
     * @param array<string|int, mixed> $params
     * @return list<array<string, mixed>>
     */
    public function select(string $query, array $params = []): array
    {
        $rows = [];
        foreach ($this->connection()->select($query, $params) as $row) {
            if (is_array($row)) {
                $rows[] = $this->normalizeRow($row);
            }
        }

        return $rows;
    }

    /**
     * @param array<string|int, mixed> $params
     * @return array<string, mixed>|null
     */
    public function selectOne(string $query, array $params = []): ?array
    {
        $row = $this->connection()->selectOne($query, $params);

        return $row === null ? null : $this->normalizeRow($row);
    }

    /** @param array<mixed, mixed> $row
     *  @return array<string, mixed>
     */
    private function normalizeRow(array $row): array
    {
        $normalized = [];
        foreach ($row as $key => $value) {
            if (is_string($key)) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }

    /** @param array<string|int, mixed> $params */
    public function insert(string $query, array $params = []): bool
    {
        return $this->connection()->insert($query, $params);
    }

    /** @param array<string|int, mixed> $params */
    public function update(string $query, array $params = []): int
    {
        return $this->connection()->update($query, $params);
    }

    /** @param array<string|int, mixed> $params */
    public function delete(string $query, array $params = []): int
    {
        return $this->connection()->delete($query, $params);
    }

    /** @param array<string|int, mixed> $params */
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
