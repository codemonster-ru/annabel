<?php

namespace Codemonster\Database\Contracts;

interface QueryBuilderInterface
{
    /** @param string|list<string|\Codemonster\Database\Query\RawExpression> ...$columns */
    public function select(string|array ...$columns): static;
    public function selectRaw(string $expression): static;
    public function distinct(): static;
    public function where(string|callable $column, mixed $operator = null, mixed $value = null, string $boolean = 'AND'): static;
    public function orWhere(string|callable $column, mixed $operator = null, mixed $value = null): static;
    /** @param list<mixed> $values */
    public function whereIn(string $column, array $values, string $boolean = 'AND'): static;
    /** @param list<mixed> $values */
    public function orWhereIn(string $column, array $values): static;
    /** @param list<mixed> $values */
    public function whereNotIn(string $column, array $values, string $boolean = 'AND'): static;
    /** @param list<mixed> $values */
    public function orWhereNotIn(string $column, array $values): static;
    public function setEmptyWhereInBehavior(string $behavior): static;
    public function setEmptyWhereNotInBehavior(string $behavior): static;
    public function whereNull(string $column, string $boolean = 'AND'): static;
    public function orWhereNull(string $column): static;
    public function whereNotNull(string $column, string $boolean = 'AND'): static;
    public function orWhereNotNull(string $column): static;
    /** @param array{0: mixed, 1: mixed} $range */
    public function whereBetween(string $column, array $range, string $boolean = 'AND'): static;
    /** @param array{0: mixed, 1: mixed} $range */
    public function orWhereBetween(string $column, array $range): static;
    /** @param array{0: mixed, 1: mixed} $range */
    public function whereNotBetween(string $column, array $range, string $boolean = 'AND'): static;
    /** @param array{0: mixed, 1: mixed} $range */
    public function orWhereNotBetween(string $column, array $range): static;
    /** @param list<mixed> $bindings */
    public function whereRaw(string $expression, array $bindings = [], string $boolean = 'AND'): static;
    /** @param list<mixed> $bindings */
    public function orWhereRaw(string $expression, array $bindings = []): static;
    public function join(string $table, string|callable $first, ?string $operator = null, ?string $second = null, string $type = 'INNER'): static;
    public function leftJoin(string $table, string|callable $first, ?string $operator = null, ?string $second = null): static;
    public function rightJoin(string $table, string|callable $first, ?string $operator = null, ?string $second = null): static;
    public function crossJoin(string $table): static;
    /** @param string|list<string> $columns */
    public function groupBy(string|array $columns): static;
    public function having(string $column, string $operator, mixed $value, string $boolean = 'AND'): static;
    public function orHaving(string $column, string $operator, mixed $value): static;
    public function havingRaw(string $expression, string $boolean = 'AND'): static;
    public function orderBy(string $column, string $direction = 'asc'): static;
    public function orderByRaw(string $expression): static;
    public function limit(int $value): static;
    public function offset(int $value): static;
    /** @return list<array<string, mixed>> */
    public function get(): array;
    /** @return array<string, mixed>|null */
    public function first(): ?array;
    public function toSql(): string;
    /** @return list<mixed> */
    public function getBindings(): array;
    /** @param array<string, mixed> $values */
    public function insert(array $values): bool;
    /** @param array<string, mixed> $values */
    public function insertGetId(array $values): int;
    /** @param array<string, mixed> $values */
    public function update(array $values): int;
    public function delete(): int;
    public function count(string $column = '*'): int;
    public function sum(string $column): float|int;
    public function avg(string $column): float|int;
    public function min(string $column): float|int;
    public function max(string $column): float|int;
    public function exists(): bool;
    public function doesntExist(): bool;
    public function value(string $column): mixed;
    /** @return array<int|string, mixed> */
    public function pluck(string $column, ?string $key = null): array;
    public function forPage(int $page, int $perPage): static;
    /**
     * @return array{
     *     data: list<array<string, mixed>>,
     *     per_page: int,
     *     current_page: int,
     *     next_page: int|null,
     *     prev_page: int|null
     * }
     */
    public function simplePaginate(int $perPage = 15, int $page = 1): array;
}
