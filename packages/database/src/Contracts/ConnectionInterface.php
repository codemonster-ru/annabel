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
    public function select(string $query, array $params = []): array;

    public function selectOne(string $query, array $params = []): ?array;

    public function insert(string $query, array $params = []): bool;

    public function update(string $query, array $params = []): int;

    public function delete(string $query, array $params = []): int;

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
