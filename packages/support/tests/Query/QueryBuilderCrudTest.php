<?php

namespace Codemonster\Support\Tests\Query;

use Codemonster\Database\Query\QueryBuilder;
use Codemonster\Support\Tests\Fakes\FakeConnection;
use PHPUnit\Framework\TestCase;

class QueryBuilderCrudTest extends TestCase
{
    protected FakeConnection $connection;

    protected function setUp(): void
    {
        $this->connection = new FakeConnection();
    }

    /* -----------------------------------------------------------------
     |  INSERT
     | -----------------------------------------------------------------
     */

    public function testInsertGeneratesCorrectSqlAndBindings()
    {
        $builder = new QueryBuilder($this->connection, 'users');

        $result = $builder->insert([
            'name' => 'Vasya',
            'email' => 'k@example.com',
        ]);

        $this->assertTrue($result);
        $this->assertSame(
            'INSERT INTO `users` (`name`, `email`) VALUES (?, ?)',
            $this->connection->lastSql,
        );
        $this->assertSame(
            ['Vasya', 'k@example.com'],
            $this->connection->lastBindings,
        );
    }

    public function testInsertGetIdReturnsId()
    {
        $builder = new QueryBuilder($this->connection, 'ideas');

        $this->connection->getPdo()->exec('CREATE TABLE test (id INTEGER PRIMARY KEY AUTOINCREMENT)');

        $id = $builder->insertGetId(['title' => 'New idea']);

        $this->assertSame(
            'INSERT INTO `ideas` (`title`) VALUES (?)',
            $this->connection->lastSql,
        );
        $this->assertSame(['New idea'], $this->connection->lastBindings);
        $this->assertTrue(is_int($id), 'insertGetId must return a number (ID)');
    }

    /* -----------------------------------------------------------------
     |  UPDATE
     | -----------------------------------------------------------------
     */

    public function testUpdateGeneratesSqlAndBindings()
    {
        $builder = new QueryBuilder($this->connection, 'users');

        $updated = $builder
            ->where('id', 10)
            ->update([
                'name' => 'Updated',
                'active' => 0,
            ]);

        $this->assertSame(1, $updated);
        $this->assertSame(
            'UPDATE `users` SET `name` = ?, `active` = ? WHERE `id` = ?',
            $this->connection->lastSql,
        );
        $this->assertSame(
            ['Updated', 0, 10],
            $this->connection->lastBindings,
        );
    }

    /* -----------------------------------------------------------------
     |  DELETE
     | -----------------------------------------------------------------
     */

    public function testDeleteGeneratesSqlAndBindings()
    {
        $builder = new QueryBuilder($this->connection, 'logs');

        $deleted = $builder
            ->where('id', 5)
            ->delete();

        $this->assertSame(1, $deleted);
        $this->assertSame(
            'DELETE FROM `logs` WHERE `id` = ?',
            $this->connection->lastSql,
        );
        $this->assertSame([5], $this->connection->lastBindings);
    }
}
