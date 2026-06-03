<?php

namespace Codemonster\Database\Tests;

use Codemonster\Database\Contracts\ConnectionInterface;
use Codemonster\Database\Query\QueryBuilder;
use Codemonster\Database\Schema\Schema;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function fakeConnection(): ConnectionInterface
    {
        return new class implements ConnectionInterface {

            public array $log = [];
            public array $results = [];
            public array $executed = [];

            public bool $inTransaction = false;

            public function select(string $query, array $params = []): array
            {
                $this->executed[] = [$query, $params];
                $this->log[] = ['select', $query, $params];

                return $this->results[$query] ?? [];
            }

            public function selectOne(string $query, array $params = []): ?array
            {
                $this->executed[] = [$query, $params];
                $this->log[] = ['selectOne', $query, $params];

                $rows = $this->results[$query] ?? [];

                return $rows[0] ?? null;
            }

            public function insert(string $query, array $params = []): bool
            {
                $this->executed[] = [$query, $params];
                $this->log[] = ['insert', $query, $params];

                return true;
            }

            public function update(string $query, array $params = []): int
            {
                $this->executed[] = [$query, $params];
                $this->log[] = ['update', $query, $params];

                return 1;
            }

            public function delete(string $query, array $params = []): int
            {
                $this->executed[] = [$query, $params];
                $this->log[] = ['delete', $query, $params];

                return 1;
            }

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
                return new Schema($this, new \Codemonster\Database\Schema\Grammar());
            }

            public function getPdo(): \PDO
            {
                return new \PDO('sqlite::memory:');
            }
        };
    }
}
