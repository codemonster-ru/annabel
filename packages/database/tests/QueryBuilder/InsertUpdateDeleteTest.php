<?php

namespace Codemonster\Database\Tests\QueryBuilder;

use Codemonster\Database\Query\QueryBuilder;
use Codemonster\Database\Tests\TestCase;

class InsertUpdateDeleteTest extends TestCase
{
    public function test_insert_sql()
    {
        $connection = $this->fakeConnection();

        $qb = new QueryBuilder($connection, 'users');

        $qb->insert(['name' => 'John']);

        $this->assertCount(1, $connection->executed);

        [$sql, $bindings] = $connection->executed[0];

        $this->assertEquals(
            'INSERT INTO `users` (`name`) VALUES (?)',
            $sql
        );

        $this->assertEquals(['John'], $bindings);
    }
}
