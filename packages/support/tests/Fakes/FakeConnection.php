<?php

namespace Codemonster\Support\Tests\Fakes;

use Codemonster\Database\Contracts\ConnectionInterface;
use Codemonster\Database\Exceptions\QueryException;
use Codemonster\Database\Query\QueryBuilder;
use PDO;

class FakeConnection implements ConnectionInterface
{
    public ?string $lastSql = null;
    public array $lastBindings = [];

    public array $selectResult = [];
    public ?array $selectOneResult = null;

    public array $queries = [];

    public function select(string $query, array $params = []): array
    {
        $this->lastSql = $query;
        $this->lastBindings = $params;

        return $this->selectResult;
    }

    public function selectOne(string $query, array $params = []): ?array
    {
        $this->lastSql = $query;
        $this->lastBindings = $params;

        return $this->selectOneResult;
    }

    public function insert(string $query, array $params = []): bool
    {
        throw new QueryException('Not implemented in FakeConnection::insert');
    }

    public function update(string $query, array $params = []): int
    {
        throw new QueryException('Not implemented in FakeConnection::update');
    }

    public function delete(string $query, array $params = []): int
    {
        throw new QueryException('Not implemented in FakeConnection::delete');
    }

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
