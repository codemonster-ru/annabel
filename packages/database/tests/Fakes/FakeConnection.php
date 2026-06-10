<?php

namespace Codemonster\Database\Tests\Fakes;

use Codemonster\Database\Contracts\ConnectionInterface;
use Codemonster\Database\Contracts\QueryBuilderInterface;
use Codemonster\Database\Schema\Schema;
use PDO;

class FakeConnection implements ConnectionInterface
{
    public array $executed = [];
    public array $migrations = [];
    public array $tables = [];
    public array $tableReads = [];

    public bool $transactionStarted = false;
    public bool $transactionCommitted = false;
    public bool $transactionRolledBack = false;

    public function select(string $query, array $params = []): array
    {
        $this->executed[] = ['select', $query, $params];

        if (str_contains($query, 'FROM `migrations`')) {
            return array_map(
                fn (string $name) => [
                    'migration' => $name,
                    'batch' => 1,
                ],
                $this->migrations,
            );
        }

        return [];
    }

    public function selectOne(string $query, array $params = []): ?array
    {
        $rows = $this->select($query, $params);

        return $rows[0] ?? null;
    }

    public function insert(string $query, array $params = []): bool
    {
        $this->executed[] = ['insert', $query, $params];

        return true;
    }

    public function update(string $query, array $params = []): int
    {
        $this->executed[] = ['update', $query, $params];

        return 1;
    }

    public function delete(string $query, array $params = []): int
    {
        $this->executed[] = ['delete', $query, $params];

        return 1;
    }

    public function statement(string $query, array $params = []): bool
    {
        $this->executed[] = ['statement', $query, $params];

        if (str_starts_with($query, 'INSERT INTO') && str_contains($query, 'migrations')) {
            $migrationName = $params[0] ?? null;

            if ($migrationName !== null) {
                $this->migrations[] = $migrationName;
            }
        }

        return true;
    }

    public function beginTransaction(): bool
    {
        $this->transactionStarted = true;

        return true;
    }

    public function commit(): bool
    {
        $this->transactionCommitted = true;

        return true;
    }

    public function rollBack(): bool
    {
        $this->transactionRolledBack = true;

        return true;
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

            $this->commit();

            return $result;
        } catch (\Throwable $e) {
            $this->rollBack();

            throw $e;
        }
    }

    public function table(string $table): QueryBuilderInterface
    {
        return new FakeQueryBuilder($this, $table);
    }

    public function schema(): Schema
    {
        return new Schema($this, new \Codemonster\Database\Schema\Grammar());
    }

    public function getPdo(): PDO
    {
        throw new \RuntimeException('FakeConnection does not use real PDO');
    }
}
