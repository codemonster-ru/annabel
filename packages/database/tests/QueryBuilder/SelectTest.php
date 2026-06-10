<?php

namespace Codemonster\Database\Tests\QueryBuilder;

use Codemonster\Database\Query\QueryBuilder;
use Codemonster\Database\Tests\TestCase;

class SelectTest extends TestCase
{
    public function test_simple_select()
    {
        $connection = $this->fakeConnection();

        $qb = new QueryBuilder($connection, 'users');

        $sql = $qb->toSql();

        $this->assertEquals('SELECT * FROM `users`', $sql);
    }

    public function test_select_raw_and_distinct()
    {
        $qb = new QueryBuilder($this->fakeConnection(), 'users');

        $sql = $qb->select('id')->selectRaw('COUNT(*) as total')->distinct()->toSql();

        $this->assertStringContainsString('SELECT DISTINCT', $sql);
        $this->assertStringContainsString('`id`, COUNT(*) as total', $sql);
    }

    public function test_order_by_raw()
    {
        $qb = new QueryBuilder($this->fakeConnection(), 'users');

        $sql = $qb->orderByRaw('FIELD(status, "active", "pending", "disabled")')->toSql();

        $this->assertStringContainsString('ORDER BY FIELD(status, "active", "pending", "disabled")', $sql);
    }

    public function test_having_raw()
    {
        $qb = new QueryBuilder($this->fakeConnection(), 'users');

        $sql = $qb->groupBy('role')->havingRaw('COUNT(*) > 1')->toSql();

        $this->assertStringContainsString('HAVING COUNT(*) > 1', $sql);
    }

    public function test_pluck_with_alias()
    {
        $conn = $this->fakeConnection();
        $qb = new QueryBuilder($conn, 'users');

        $expectedSql = 'SELECT `name` AS `label`, `id` FROM `users`';
        $conn->results[$expectedSql] = [
            ['id' => 1, 'label' => 'Alice'],
        ];

        $result = $qb->pluck('name as label', 'id');

        $this->assertSame([1 => 'Alice'], $result);
    }

    public function test_select_with_alias_and_expression()
    {
        $qb = new QueryBuilder($this->fakeConnection(), 'users');

        $sql = $qb->select('users.name as label', 'COUNT(*) as total')->toSql();

        $this->assertSame(
            'SELECT `users`.`name` AS `label`, COUNT(*) AS `total` FROM `users`',
            $sql,
        );
    }

    public function test_select_with_alias_without_as()
    {
        $qb = new QueryBuilder($this->fakeConnection(), 'users');

        $sql = $qb->select('users.name label')->toSql();

        $this->assertSame('SELECT `users`.`name` AS `label` FROM `users`', $sql);
    }

    public function test_select_table_star()
    {
        $qb = new QueryBuilder($this->fakeConnection(), 'users');

        $sql = $qb->select('users.*')->toSql();

        $this->assertSame('SELECT `users`.* FROM `users`', $sql);
    }

    public function test_pluck_with_table_column()
    {
        $conn = $this->fakeConnection();
        $qb = new QueryBuilder($conn, 'users');

        $expectedSql = 'SELECT `users`.`name`, `users`.`id` FROM `users`';
        $conn->results[$expectedSql] = [
            ['name' => 'Alice', 'id' => 1],
        ];

        $result = $qb->pluck('users.name', 'users.id');

        $this->assertSame([1 => 'Alice'], $result);
    }

    public function test_value_with_alias_and_table_column()
    {
        $conn = $this->fakeConnection();

        $qb = new QueryBuilder($conn, 'users');
        $sqlAlias = 'SELECT `name` AS `label` FROM `users` LIMIT 1';
        $conn->results[$sqlAlias] = [['label' => 'Alice']];

        $aliasValue = $qb->value('name as label');

        $this->assertSame('Alice', $aliasValue);

        $qb = new QueryBuilder($conn, 'users');
        $sqlTable = 'SELECT `users`.`name` FROM `users` LIMIT 1';
        $conn->results[$sqlTable] = [['name' => 'Bob']];

        $tableValue = $qb->value('users.name');

        $this->assertSame('Bob', $tableValue);
    }

    public function test_pluck_with_alias_without_as()
    {
        $conn = $this->fakeConnection();
        $qb = new QueryBuilder($conn, 'users');

        $expectedSql = 'SELECT `users`.`name` AS `label`, `users`.`id` AS `key` FROM `users`';
        $conn->results[$expectedSql] = [
            ['label' => 'Alice', 'key' => 1],
        ];

        $result = $qb->pluck('users.name label', 'users.id key');

        $this->assertSame([1 => 'Alice'], $result);
    }

    public function test_value_with_expression_alias_without_as()
    {
        $conn = $this->fakeConnection();
        $qb = new QueryBuilder($conn, 'users');

        $expectedSql = 'SELECT COUNT(*) AS `total` FROM `users` LIMIT 1';
        $conn->results[$expectedSql] = [
            ['total' => 3],
        ];

        $result = $qb->value('COUNT(*) total');

        $this->assertSame(3, $result);
    }
}
