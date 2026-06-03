<?php

namespace Codemonster\Database\Tests\Migrations;

use Codemonster\Database\Contracts\ConnectionInterface;
use Codemonster\Database\Migrations\MigrationRepository;
use Codemonster\Database\Query\QueryBuilder;
use Codemonster\Database\Schema\Grammars\SQLiteGrammar;
use Codemonster\Database\Schema\Schema;
use Codemonster\Database\Tests\Fakes\FakeConnection;
use Codemonster\Database\Tests\TestCase;
use PDO;

class RepositoryTest extends TestCase
{
    public function test_repository_logs_migration()
    {
        $conn = new FakeConnection();
        $repo = new MigrationRepository($conn);

        $repo->log('2025_01_01_test', 1);

        $ran = $repo->getRan();

        $this->assertCount(1, $ran);
        $this->assertSame('2025_01_01_test', $ran[0]['migration']);
        $this->assertSame(1, $ran[0]['batch']);
    }

    public function test_repository_uses_sqlite_ddl_when_driver_is_sqlite()
    {
        $conn = new class implements ConnectionInterface {
            public array $statements = [];
            private PDO $pdo;

            public function __construct()
            {
                $this->pdo = new PDO('sqlite::memory:');
            }

            public function select(string $query, array $params = []): array
            {
                return [];
            }

            public function selectOne(string $query, array $params = []): ?array
            {
                return null;
            }

            public function insert(string $query, array $params = []): bool
            {
                return true;
            }

            public function update(string $query, array $params = []): int
            {
                return 0;
            }

            public function delete(string $query, array $params = []): int
            {
                return 0;
            }

            public function statement(string $query, array $params = []): bool
            {
                $this->statements[] = $query;

                return true;
            }

            public function table(string $table): QueryBuilder
            {
                return new QueryBuilder($this, $table);
            }

            public function beginTransaction(): bool
            {
                return true;
            }

            public function commit(): bool
            {
                return true;
            }

            public function rollBack(): bool
            {
                return true;
            }

            public function transaction(callable $callback): mixed
            {
                return $callback($this);
            }

            public function getPdo(): PDO
            {
                return $this->pdo;
            }

            public function schema(): Schema
            {
                return new Schema($this, new SQLiteGrammar());
            }
        };

        $repo = new MigrationRepository($conn);

        $this->assertNotEmpty($conn->statements);
        $this->assertStringContainsString('CREATE TABLE IF NOT EXISTS "migrations"', $conn->statements[0]);
        $this->assertStringContainsString('AUTOINCREMENT', $conn->statements[0]);
    }
}
