<?php

declare(strict_types=1);

namespace Codemonster\Support\Tests\Fakes;

use Codemonster\Database\Contracts\ConnectionInterface;
use Codemonster\Database\Exceptions\QueryException;
use Codemonster\Database\Query\QueryBuilder;
use Codemonster\Database\Schema\Schema;
use PDO;

class FakeConnection implements ConnectionInterface
{
    public ?string $lastSql = null;
    /** @var array<int|string, mixed> */
    public array $lastBindings = [];

    /** @var list<array<string, mixed>> */
    public array $selectResult = [];
    /** @var array<string, mixed>|null */
    public ?array $selectOneResult = null;

    /** @var list<array{0: string}> */
    public array $queries = [];

    /**
     * @param array<int|string, mixed> $params
     * @return list<array<string, mixed>>
     */
    public function select(string $query, array $params = []): array
    {
        $this->lastSql = $query;
        $this->lastBindings = $params;

        return $this->selectResult;
    }

    /**
     * @param array<int|string, mixed> $params
     * @return array<string, mixed>|null
     */
    public function selectOne(string $query, array $params = []): ?array
    {
        $this->lastSql = $query;
        $this->lastBindings = $params;

        return $this->selectOneResult;
    }

    /** @param array<int|string, mixed> $params */
    public function insert(string $query, array $params = []): bool
    {
        throw new QueryException('Not implemented in FakeConnection::insert');
    }

    /** @param array<int|string, mixed> $params */
    public function update(string $query, array $params = []): int
    {
        throw new QueryException('Not implemented in FakeConnection::update');
    }

    /** @param array<int|string, mixed> $params */
    public function delete(string $query, array $params = []): int
    {
        throw new QueryException('Not implemented in FakeConnection::delete');
    }

    /** @param array<int|string, mixed> $params */
    public function statement(string $query, array $params = []): bool
    {
        throw new QueryException('Not implemented in FakeConnection::statement');
    }

    public function getPdo(): PDO
    {
        return new PDO('sqlite::memory:');
    }

    public function table(string $table): QueryBuilder
    {
        return new QueryBuilder($this, $table);
    }

    public function schema(): Schema
    {
        return Schema::forConnection($this);
    }

    public function beginTransaction(): bool
    {
        $this->queries[] = ['beginTransaction'];

        return true;
    }

    public function commit(): bool
    {
        $this->queries[] = ['commit'];

        return true;
    }

    public function rollBack(): bool
    {
        $this->queries[] = ['rollBack'];

        return true;
    }

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
}
