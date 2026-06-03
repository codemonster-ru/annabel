<?php

namespace Codemonster\Database\Tests\QueryBuilder;

use Codemonster\Database\Query\QueryBuilder;
use Codemonster\Database\Tests\TestCase;

class JoinTest extends TestCase
{
    public function test_inner_join()
    {
        $qb = new QueryBuilder($this->fakeConnection(), 'users');

        $qb->join(
            'posts',
            fn($j) =>
            $j->on('users.id', '=', 'posts.user_id')
        );

        $sql = $qb->toSql();

        $this->assertStringContainsString(
            'INNER JOIN `posts` ON `users`.`id` = `posts`.`user_id`',
            $sql
        );
    }

    public function test_join_with_where_conditions()
    {
        $qb = new QueryBuilder($this->fakeConnection(), 'users');

        $qb->join('posts', function ($j) {
            $j->on('users.id', '=', 'posts.user_id')
                ->where('posts.published', '=', 1);
        });

        $sql = $qb->toSql();

        $this->assertStringContainsString(
            'INNER JOIN `posts` ON `users`.`id` = `posts`.`user_id` AND `posts`.`published` = ?',
            $sql
        );
        $this->assertSame([1], $qb->getBindings());
    }

    public function test_join_with_multiple_where_conditions()
    {
        $qb = new QueryBuilder($this->fakeConnection(), 'users');

        $qb->join('posts', function ($j) {
            $j->on('users.id', '=', 'posts.user_id')
                ->where('posts.published', '=', 1)
                ->where('posts.archived', '=', 0);
        });

        $sql = $qb->toSql();

        $this->assertStringContainsString(
            'INNER JOIN `posts` ON `users`.`id` = `posts`.`user_id` AND `posts`.`published` = ? AND `posts`.`archived` = ?',
            $sql
        );
        $this->assertSame([1, 0], $qb->getBindings());
    }
}
