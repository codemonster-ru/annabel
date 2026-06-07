<?php

namespace Codemonster\Database\Contracts;

use PDO;
use Codemonster\Database\Schema\Schema;
use Codemonster\Database\Contracts\QueryBuilderInterface;

/**
 * Database connection abstraction.
 */
interface ConnectionInterface
{
    /**
     * @param array<int|string, mixed> $params
     * @return list<array<string, mixed>>
     */
    public function select(string $query, array $params = []): array;

    /**
     * @param array<int|string, mixed> $params
     * @return array<string, mixed>|null
     */
    public function selectOne(string $query, array $params = []): ?array;

    /** @param array<int|string, mixed> $params */
    public function insert(string $query, array $params = []): bool;

    /** @param array<int|string, mixed> $params */
    public function update(string $query, array $params = []): int;

    /** @param array<int|string, mixed> $params */
    public function delete(string $query, array $params = []): int;

    /** @param array<int|string, mixed> $params */
    public function statement(string $query, array $params = []): bool;

    /**
     * @return \Codemonster\Database\Contracts\QueryBuilderInterface
     */
    public function table(string $table): QueryBuilderInterface;

    public function beginTransaction(): bool;

    public function commit(): bool;

    public function rollBack(): bool;

    /**
     * @template T
     * @param callable(self):T $callback
     * @return T
     */
    public function transaction(callable $callback): mixed;

    public function getPdo(): PDO;

    public function schema(): Schema;
}
