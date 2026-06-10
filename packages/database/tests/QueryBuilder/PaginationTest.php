<?php

namespace Codemonster\Database\Tests\QueryBuilder;

use Codemonster\Database\Query\QueryBuilder;
use Codemonster\Database\Tests\TestCase;

class PaginationTest extends TestCase
{
    public function test_first_does_not_mutate_builder()
    {
        $qb = new QueryBuilder($this->fakeConnection(), 'users');

        try {
            $qb->first();
        } catch (\Throwable $e) {
        }

        $sql = $qb->toSql();

        $this->assertStringNotContainsString('LIMIT', $sql);
    }
}
