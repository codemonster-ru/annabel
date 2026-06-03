<?php

namespace Codemonster\Database\Tests\Schema;

use Codemonster\Database\Contracts\ConnectionInterface;
use Codemonster\Database\Query\QueryBuilder;
use Codemonster\Database\Schema\Grammars\SQLiteGrammar;
use Codemonster\Database\Schema\MySqlGrammar;
use Codemonster\Database\Schema\Schema;
use Codemonster\Database\Tests\TestCase;
use PDO;

class SchemaTest extends TestCase
{
    public function test_drop_and_drop_if_exists()
    {
        $conn = new class implements ConnectionInterface {
            public array $statements = [];

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
                return new PDO('sqlite::memory:');
            }

            public function schema(): Schema
            {
                return new Schema($this, new SQLiteGrammar());
            }
        };

        $schema = new Schema($conn, new MySqlGrammar());

        $schema->drop('users');
        $schema->dropIfExists('posts');
        $schema->table('users', fn($table) => $table->string('name'));

        $this->assertSame('DROP TABLE `users`', $conn->statements[0]);
        $this->assertSame('DROP TABLE IF EXISTS `posts`', $conn->statements[1]);
        $this->assertSame('ALTER TABLE `users` ADD COLUMN `name` VARCHAR(255) NOT NULL', $conn->statements[2]);
    }

    public function test_sqlite_schema_rename_and_drop_if_exists()
    {
        $conn = new class implements ConnectionInterface {
            public array $statements = [];

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
                return new PDO('sqlite::memory:');
            }

            public function schema(): Schema
            {
                return Schema::forConnection($this);
            }
        };

        $schema = Schema::forConnection($conn);

        $schema->dropIfExists('users');
        $schema->table('users', fn($table) => $table->rename('accounts'));

        $this->assertSame('DROP TABLE IF EXISTS "users"', $conn->statements[0]);
        $this->assertSame('ALTER TABLE "users" RENAME TO "accounts"', $conn->statements[1]);
    }
}
