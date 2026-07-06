<?php

namespace Codemonster\Database\Tests\QueryBuilder;

use Codemonster\Database\Query\QueryBuilder;
use Codemonster\Database\Tests\TestCase;

class WhereTest extends TestCase
{
    public function test_basic_where(): void
    {
        $qb = new QueryBuilder($this->fakeConnection(), 'users');

        $sql = $qb->where('id', '=', 5)->toSql();

        $this->assertStringContainsString('WHERE `id` = ?', $sql);
    }

    public function test_callable_function_name_is_treated_as_column(): void
    {
        $qb = new QueryBuilder($this->fakeConnection(), 'users');

        $sql = $qb->where('key', '=', 'account')->toSql();

        $this->assertStringContainsString('WHERE `key` = ?', $sql);
        $this->assertSame(['account'], $qb->getBindings());
    }

    public function test_nested_where_groups(): void
    {
        $qb = new QueryBuilder($this->fakeConnection(), 'users');

        $qb->where(function (QueryBuilder $q): void {
            $q->where('age', '>', 18)
                ->orWhere('role', '=', 'admin');
        });

        $sql = $qb->toSql();

        $this->assertStringContainsString(
            '(`age` > ? OR `role` = ?)',
            $sql,
        );
    }

    public function test_where_in_with_empty_values(): void
    {
        $qb = new QueryBuilder($this->fakeConnection(), 'users');

        $sql = $qb->whereIn('id', [])->toSql();

        $this->assertStringContainsString('WHERE (0 = 1)', $sql);
    }

    public function test_where_not_in_with_empty_values(): void
    {
        $qb = new QueryBuilder($this->fakeConnection(), 'users');

        $sql = $qb->whereNotIn('id', [])->toSql();

        $this->assertStringContainsString('WHERE (1 = 1)', $sql);
    }

    public function test_where_raw_preserves_expression_and_bindings(): void
    {
        $qb = new QueryBuilder($this->fakeConnection(), 'users');

        $sql = $qb->whereRaw('age > ?', [18])->toSql();

        $this->assertStringContainsString('WHERE (age > ?)', $sql);
        $this->assertSame([18], $qb->getBindings());
    }

    public function test_empty_where_in_behavior_can_return_all(): void
    {
        $qb = new QueryBuilder($this->fakeConnection(), 'users');

        $sql = $qb
            ->setEmptyWhereInBehavior(QueryBuilder::EMPTY_CONDITION_ALL)
            ->whereIn('id', [])
            ->toSql();

        $this->assertStringContainsString('WHERE (1 = 1)', $sql);
    }

    public function test_empty_where_not_in_behavior_can_return_none(): void
    {
        $qb = new QueryBuilder($this->fakeConnection(), 'users');

        $sql = $qb
            ->setEmptyWhereNotInBehavior(QueryBuilder::EMPTY_CONDITION_NONE)
            ->whereNotIn('id', [])
            ->toSql();

        $this->assertStringContainsString('WHERE (0 = 1)', $sql);
    }

    public function test_empty_where_in_behavior_can_throw(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $qb = new QueryBuilder($this->fakeConnection(), 'users');

        $qb
            ->setEmptyWhereInBehavior(QueryBuilder::EMPTY_CONDITION_EXCEPTION)
            ->whereIn('id', []);
    }
}
