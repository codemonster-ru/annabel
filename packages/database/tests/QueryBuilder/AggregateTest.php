<?php

namespace Codemonster\Database\Tests\QueryBuilder;

use Codemonster\Database\Query\QueryBuilder;
use Codemonster\Database\Tests\TestCase;

class AggregateTest extends TestCase
{
    public function test_count_does_not_mutate_builder()
    {
        $qb = new QueryBuilder($this->fakeConnection(), 'users');
        $qb->where('active', '=', 1);

        try {
            $qb->count();
        } catch (\Throwable $e) {
        }

        $sql = $qb->toSql();

        $this->assertStringNotContainsString('COUNT', $sql);
    }

    public function test_exists_returns_true_and_does_not_mutate_builder()
    {
        $connection = $this->fakeConnection();
        $qb = new QueryBuilder($connection, 'users');

        $expectedSql = 'SELECT 1 FROM `users` LIMIT 1';
        $connection->results[$expectedSql] = [['1' => 1]];

        $this->assertTrue($qb->exists());
        $this->assertSame('SELECT * FROM `users`', $qb->toSql());
    }
}
