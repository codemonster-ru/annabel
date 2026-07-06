<?php

declare(strict_types=1);

namespace Codemonster\Database\Tests;

use Codemonster\Database\Connection;
use Codemonster\Database\Exceptions\QueryException;
use Codemonster\Database\Schema\Grammars\SQLiteGrammar;

class ConnectionTest extends TestCase
{
    public function test_connection_throws_query_exception_on_bad_credentials(): void
    {
        $this->expectException(QueryException::class);

        new Connection([
            'host' => '127.0.0.1',
            'database' => 'wrong',
            'username' => 'root',
            'password' => 'nope',
        ]);
    }

    public function test_select_queries_are_prepared(): void
    {
        $pdo = new class () {
            /** @var list<string> */
            public array $executed = [];

            public function prepare(string $query): \PDOStatement
            {
                $this->executed[] = $query;

                return new class () extends \PDOStatement {
                    /** @param array<int|string, mixed>|null $params */
                    public function execute(?array $params = null): bool
                    {
                        return true;
                    }

                    /** @return list<array<string, mixed>> */
                    public function fetchAll(int $mode = \PDO::ATTR_DEFAULT_FETCH_MODE, ...$args): array
                    {
                        return [];
                    }
                };
            }
        };

        $ref = new \ReflectionClass(Connection::class);

        /** @var Connection $connection */
        $connection = $ref->newInstanceWithoutConstructor();

        $prop = $ref->getProperty('pdo');
        $prop->setAccessible(true);
        $prop->setValue($connection, $pdo);

        $connection->select('SELECT * FROM users');

        $this->assertEquals(
            ['SELECT * FROM users'],
            $pdo->executed,
        );
    }

    public function test_transaction_commits_when_open(): void
    {
        $pdo = new class () {
            public bool $began = false;
            public bool $committed = false;

            public function beginTransaction(): bool
            {
                $this->began = true;

                return true;
            }

            public function commit(): bool
            {
                $this->committed = true;

                return true;
            }

            public function rollBack(): bool
            {
                return true;
            }

            public function inTransaction(): bool
            {
                return true;
            }
        };

        $connection = $this->makeConnectionWithPdo($pdo);

        $result = $connection->transaction(fn () => 'ok');

        $this->assertSame('ok', $result);
        $this->assertTrue($pdo->began);
        $this->assertTrue($pdo->committed);
    }

    public function test_transaction_skips_commit_when_closed(): void
    {
        $pdo = new class () {
            public bool $committed = false;

            public function beginTransaction(): bool
            {
                return true;
            }

            public function commit(): bool
            {
                $this->committed = true;

                return true;
            }

            public function rollBack(): bool
            {
                return true;
            }

            public function inTransaction(): bool
            {
                return false;
            }
        };

        $connection = $this->makeConnectionWithPdo($pdo);

        $connection->transaction(fn () => null);

        $this->assertFalse($pdo->committed);
    }

    public function test_transaction_rolls_back_on_exception(): void
    {
        $pdo = new class () {
            public bool $rolledBack = false;

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
                $this->rolledBack = true;

                return true;
            }

            public function inTransaction(): bool
            {
                return true;
            }
        };

        $connection = $this->makeConnectionWithPdo($pdo);

        try {
            $connection->transaction(function () {
                throw new \RuntimeException('fail');
            });
        } catch (\RuntimeException $e) {
        }

        $this->assertTrue($pdo->rolledBack);
    }

    public function test_schema_uses_sqlite_grammar_for_sqlite_connection(): void
    {
        $conn = new Connection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $schema = $conn->schema();

        $ref = new \ReflectionClass($schema);
        $prop = $ref->getProperty('grammar');
        $prop->setAccessible(true);

        $this->assertInstanceOf(SQLiteGrammar::class, $prop->getValue($schema));
    }

    private function makeConnectionWithPdo(object $pdo): Connection
    {
        $ref = new \ReflectionClass(Connection::class);

        /** @var Connection $connection */
        $connection = $ref->newInstanceWithoutConstructor();

        $prop = $ref->getProperty('pdo');
        $prop->setAccessible(true);
        $prop->setValue($connection, $pdo);

        return $connection;
    }
}
