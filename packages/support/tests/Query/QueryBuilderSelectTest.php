<?php

namespace Tests\Query;

use Codemonster\Database\Query\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\FakeConnection;

class QueryBuilderSelectTest extends TestCase
{
    protected FakeConnection $connection;

    protected function setUp(): void
    {
        $this->connection = new FakeConnection();
    }

    public function testSimpleSelectAll()
    {
        $builder = new QueryBuilder($this->connection, 'users');

        $sql = $builder->toSql();
        $bindings = $builder->getBindings();

        $this->assertSame('SELECT * FROM `users`', $sql);
        $this->assertSame([], $bindings);
    }

    public function testSelectWithWhere()
    {
        $builder = new QueryBuilder($this->connection, 'users');

        $sql = $builder
            ->where('id', 10)
            ->toSql();

        $bindings = $builder->getBindings();

        $this->assertSame('SELECT * FROM `users` WHERE `id` = ?', $sql);
        $this->assertSame([10], $bindings);
    }

    public function testSelectWithWhereAndOrWhere()
    {
        $builder = new QueryBuilder($this->connection, 'users');

        $sql = $builder
            ->where('active', 1)
            ->orWhere('role', 'admin')
            ->toSql();

        $bindings = $builder->getBindings();

        $this->assertSame(
            'SELECT * FROM `users` WHERE `active` = ? OR `role` = ?',
            $sql
        );
        $this->assertSame([1, 'admin'], $bindings);
    }

    public function testSelectWithOrderLimitOffset()
    {
        $builder = new QueryBuilder($this->connection, 'users');

        $sql = $builder
            ->select('id', 'email')
            ->where('active', 1)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->offset(20)
            ->toSql();

        $bindings = $builder->getBindings();

        $this->assertSame(
            'SELECT `id`, `email` FROM `users` WHERE `active` = ? ORDER BY `created_at` DESC LIMIT 10 OFFSET 20',
            $sql
        );
        $this->assertSame([1], $bindings);
    }

    public function testGetUsesConnectionSelect()
    {
        $this->connection->selectResult = [
            ['id' => 1, 'name' => 'Test'],
        ];

        $builder = new QueryBuilder($this->connection, 'users');

        $result = $builder
            ->where('id', 1)
            ->get();

        $this->assertSame(
            'SELECT * FROM `users` WHERE `id` = ?',
            $this->connection->lastSql
        );
        $this->assertSame([1], $this->connection->lastBindings);
        $this->assertSame($this->connection->selectResult, $result);
    }

    public function testFirstUsesConnectionSelectOne()
    {
        $this->connection->selectOneResult = ['id' => 2, 'name' => 'Jane'];

        $builder = new QueryBuilder($this->connection, 'users');

        $result = $builder
            ->where('id', 2)
            ->first();

        $this->assertSame(
            'SELECT * FROM `users` WHERE `id` = ? LIMIT 1',
            $this->connection->lastSql
        );
        $this->assertSame([2], $this->connection->lastBindings);
        $this->assertSame($this->connection->selectOneResult, $result);
    }
}
