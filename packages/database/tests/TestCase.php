<?php

declare(strict_types=1);

namespace Codemonster\Database\Tests;

use Codemonster\Database\Contracts\ConnectionInterface;
use Codemonster\Database\Query\QueryBuilder;
use Codemonster\Database\Schema\Grammars\MySqlGrammar;
use Codemonster\Database\Schema\Schema;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function fakeConnection(): TestConnection
    {
        return new TestConnection();
    }
}

class TestConnection implements ConnectionInterface
{
    /** @var list<array{0: string, 1?: string, 2?: array<int|string, mixed>}> */
    public array $log = [];
    /** @var array<string, list<array<string, mixed>>> */
    public array $results = [];
    /** @var list<array{0: string, 1: array<int|string, mixed>}> */
    public array $executed = [];

    public bool $inTransaction = false;

    /**
     * @param array<int|string, mixed> $params
     * @return list<array<string, mixed>>
     */
    public function select(string $query, array $params = []): array
    {
        $this->executed[] = [$query, $params];
        $this->log[] = ['select', $query, $params];

        return $this->results[$query] ?? [];
    }

    /** @param array<int|string, mixed> $params */
    public function selectOne(string $query, array $params = []): ?array
    {
        $this->executed[] = [$query, $params];
        $this->log[] = ['selectOne', $query, $params];

        $rows = $this->results[$query] ?? [];

        return $rows[0] ?? null;
    }

    /** @param array<int|string, mixed> $params */
    public function insert(string $query, array $params = []): bool
    {
        $this->executed[] = [$query, $params];
        $this->log[] = ['insert', $query, $params];

        return true;
    }

    /** @param array<int|string, mixed> $params */
    public function update(string $query, array $params = []): int
    {
        $this->executed[] = [$query, $params];
        $this->log[] = ['update', $query, $params];

        return 1;
    }

    /** @param array<int|string, mixed> $params */
    public function delete(string $query, array $params = []): int
    {
        $this->executed[] = [$query, $params];
        $this->log[] = ['delete', $query, $params];

        return 1;
    }

    /** @param array<int|string, mixed> $params */
    public function statement(string $query, array $params = []): bool
    {
        $this->executed[] = [$query, $params];
        $this->log[] = ['statement', $query, $params];

        return true;
    }

    public function table(string $table): QueryBuilder
    {
        return new QueryBuilder($this, $table);
    }

    public function beginTransaction(): bool
    {
        $this->inTransaction = true;
        $this->log[] = ['begin'];

        return true;
    }

    public function commit(): bool
    {
        $this->inTransaction = false;
        $this->log[] = ['commit'];

        return true;
    }

    public function rollBack(): bool
    {
        $this->inTransaction = false;
        $this->log[] = ['rollback'];

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

        $result = $callback($this);

        $this->commit();

        return $result;
    }

    public function schema(): Schema
    {
        return new Schema($this, new MySqlGrammar());
    }

    public function getPdo(): \PDO
    {
        return new \PDO('sqlite::memory:');
    }
}
