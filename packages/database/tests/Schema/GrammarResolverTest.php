<?php

namespace Codemonster\Database\Tests\Schema;

use Codemonster\Database\Contracts\ConnectionInterface;
use Codemonster\Database\Query\QueryBuilder;
use Codemonster\Database\Schema\GrammarResolver;
use Codemonster\Database\Schema\Grammars\MySqlGrammar;
use Codemonster\Database\Schema\Grammars\SQLiteGrammar;
use Codemonster\Database\Schema\Schema;
use Codemonster\Database\Tests\TestCase;
use PDO;

class GrammarResolverTest extends TestCase
{
    public function test_resolver_returns_sqlite_grammar()
    {
        $resolver = new GrammarResolver();

        $conn = $this->makeConnection(new PDO('sqlite::memory:'));

        $grammar = $resolver->resolve($conn);

        $this->assertInstanceOf(SQLiteGrammar::class, $grammar);
    }

    public function test_resolver_falls_back_to_mysql_on_error()
    {
        $resolver = new GrammarResolver();

        $conn = new class () implements ConnectionInterface {
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
                throw new \RuntimeException('No PDO');
            }

            public function schema(): Schema
            {
                return Schema::forConnection($this);
            }
        };

        $grammar = $resolver->resolve($conn);

        $this->assertInstanceOf(MySqlGrammar::class, $grammar);
    }

    public function test_schema_for_connection_uses_resolver()
    {
        $conn = $this->makeConnection(new PDO('sqlite::memory:'));

        $schema = Schema::forConnection($conn);

        $ref = new \ReflectionClass($schema);
        $prop = $ref->getProperty('grammar');
        $prop->setAccessible(true);

        $this->assertInstanceOf(SQLiteGrammar::class, $prop->getValue($schema));
    }

    private function makeConnection(PDO $pdo): ConnectionInterface
    {
        return new class ($pdo) implements ConnectionInterface {
            private PDO $pdo;

            public function __construct(PDO $pdo)
            {
                $this->pdo = $pdo;
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
                return Schema::forConnection($this);
            }
        };
    }
}
