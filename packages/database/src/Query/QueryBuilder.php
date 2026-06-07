<?php

namespace Codemonster\Database\Query;

use Codemonster\Database\Contracts\ConnectionInterface;
use Codemonster\Database\Contracts\QueryBuilderInterface;

class QueryBuilder implements QueryBuilderInterface
{
    public const EMPTY_CONDITION_NONE = 'none';
    public const EMPTY_CONDITION_ALL = 'all';
    public const EMPTY_CONDITION_EXCEPTION = 'exception';

    protected ConnectionInterface $connection;

    protected string $table;

    /** @var array<int, string|RawExpression> */
    protected array $columns = ['*'];

    /**
     * @var list<
     *     array{type: 'raw', raw: string}
     *     |array{type: 'basic', column: string, direction: 'ASC'|'DESC'}
     * >
     */
    protected array $orders = [];

    protected ?int $limit = null;

    protected ?int $offset = null;

    protected WhereGroup $where;

    /** @var list<JoinClause> */
    protected array $joins = [];

    protected bool $distinct = false;

    /** @var list<string> */
    protected array $groups = [];

    /**
     * @var list<
     *     array{type: 'raw', sql: string, boolean: string}
     *     |array{type: 'basic', column: string, operator: string, value: mixed, boolean: string}
     * >
     */
    protected array $havings = [];

    protected string $emptyWhereInBehavior = self::EMPTY_CONDITION_NONE;
    protected string $emptyWhereNotInBehavior = self::EMPTY_CONDITION_ALL;

    public function __construct(ConnectionInterface $connection, string $table)
    {
        $this->connection = $connection;
        $this->table = $table;
        $this->where = new WhereGroup();
    }

    // ------------------------------------------------------
    // Basic query setup
    // ------------------------------------------------------

    /**
     * @param string|array<int, string|RawExpression> ...$columns
     */
    public function select(string|array ...$columns): static
    {
        $normalized = [];

        foreach ($columns as $column) {
            if (is_array($column)) {
                foreach ($column as $nestedColumn) {
                    $normalized[] = $nestedColumn;
                }
            } else {
                $normalized[] = $column;
            }
        }

        if ($normalized === []) {
            $normalized = ['*'];
        }

        $this->columns = $normalized;

        return $this;
    }

    public function selectRaw(string $expression): static
    {
        $this->columns[] = new RawExpression($expression);

        return $this;
    }

    public function distinct(): static
    {
        $this->distinct = true;

        return $this;
    }

    // ------------------------------------------------------
    // WHERE
    // ------------------------------------------------------

    /**
     * @param string|callable(static):void $column
     */
    public function where(string|callable $column, mixed $operator = null, mixed $value = null, string $boolean = 'AND'): static
    {
        if (!is_string($column)) {
            $group = new WhereGroup();

            $column($this->newScopedBuilder($group));

            $this->where->addGroup($group, strtoupper($boolean));

            return $this;
        }

        // where('active', 1) → '='
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        if (!is_string($operator)) {
            throw new \InvalidArgumentException('Where operator must be a string.');
        }

        $condition = new WhereCondition(
            $column,
            $operator,
            $value,
            strtoupper($boolean)
        );

        $this->where->addCondition($condition);

        return $this;
    }

    public function orWhere(string|callable $column, mixed $operator = null, mixed $value = null): static
    {
        return $this->where($column, $operator, $value, 'OR');
    }

    public function whereIn(string $column, array $values, string $boolean = 'AND'): static
    {
        if (empty($values)) {
            return $this->handleEmptyIn($this->emptyWhereInBehavior, $boolean);
        }

        $condition = new WhereCondition(
            $column,
            'IN',
            $values,
            $boolean,
            isList: true
        );

        $this->where->addCondition($condition);

        return $this;
    }

    public function orWhereIn(string $column, array $values): static
    {
        return $this->whereIn($column, $values, 'OR');
    }

    public function whereNotIn(string $column, array $values, string $boolean = 'AND'): static
    {
        if (empty($values)) {
            return $this->handleEmptyIn($this->emptyWhereNotInBehavior, $boolean);
        }

        $condition = new WhereCondition(
            $column,
            'NOT IN',
            $values,
            $boolean,
            isList: true
        );

        $this->where->addCondition($condition);

        return $this;
    }

    public function setEmptyWhereInBehavior(string $behavior): static
    {
        $this->assertEmptyInBehavior($behavior);

        $this->emptyWhereInBehavior = $behavior;

        return $this;
    }

    public function setEmptyWhereNotInBehavior(string $behavior): static
    {
        $this->assertEmptyInBehavior($behavior);

        $this->emptyWhereNotInBehavior = $behavior;

        return $this;
    }

    protected function assertEmptyInBehavior(string $behavior): void
    {
        $allowed = [
            self::EMPTY_CONDITION_NONE,
            self::EMPTY_CONDITION_ALL,
            self::EMPTY_CONDITION_EXCEPTION,
        ];

        if (!in_array($behavior, $allowed, true)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid empty IN behavior "%s".', $behavior)
            );
        }
    }

    protected function handleEmptyIn(string $behavior, string $boolean): static
    {
        return match ($behavior) {
            self::EMPTY_CONDITION_NONE => $this->whereRaw('0 = 1', [], $boolean),
            self::EMPTY_CONDITION_ALL => $this->whereRaw('1 = 1', [], $boolean),
            self::EMPTY_CONDITION_EXCEPTION => throw new \InvalidArgumentException(
                'Empty values are not allowed for whereIn/whereNotIn.'
            ),
            default => $this->whereRaw('0 = 1', [], $boolean),
        };
    }

    public function orWhereNotIn(string $column, array $values): static
    {
        return $this->whereNotIn($column, $values, 'OR');
    }

    public function whereNull(string $column, string $boolean = 'AND'): static
    {
        $condition = new WhereCondition(
            $column,
            'IS NULL',
            value: null,
            boolean: $boolean
        );

        $this->where->addCondition($condition);

        return $this;
    }

    public function orWhereNull(string $column): static
    {
        return $this->whereNull($column, 'OR');
    }

    public function whereNotNull(string $column, string $boolean = 'AND'): static
    {
        $condition = new WhereCondition(
            $column,
            'IS NOT NULL',
            value: null,
            boolean: $boolean
        );

        $this->where->addCondition($condition);

        return $this;
    }

    public function orWhereNotNull(string $column): static
    {
        return $this->whereNotNull($column, 'OR');
    }

    public function whereBetween(string $column, array $range, string $boolean = 'AND'): static
    {
        $condition = new WhereCondition(
            $column,
            'BETWEEN',
            $range,
            $boolean
        );

        $this->where->addCondition($condition);

        return $this;
    }

    public function orWhereBetween(string $column, array $range): static
    {
        return $this->whereBetween($column, $range, 'OR');
    }

    public function whereNotBetween(string $column, array $range, string $boolean = 'AND'): static
    {
        $condition = new WhereCondition(
            $column,
            'NOT BETWEEN',
            $range,
            $boolean
        );

        $this->where->addCondition($condition);

        return $this;
    }

    public function orWhereNotBetween(string $column, array $range): static
    {
        return $this->whereNotBetween($column, $range, 'OR');
    }

    public function whereRaw(string $expression, array $bindings = [], string $boolean = 'AND'): static
    {
        $condition = new WhereCondition(
            column: '',
            operator: 'RAW',
            value: [$expression, $bindings],
            boolean: $boolean
        );

        $this->where->addCondition($condition);

        return $this;
    }

    public function orWhereRaw(string $expression, array $bindings = []): static
    {
        return $this->whereRaw($expression, $bindings, 'OR');
    }

    // ------------------------------------------------------
    // JOIN
    // ------------------------------------------------------

    public function join(string $table, string|callable $first, ?string $operator = null, ?string $second = null, string $type = 'INNER'): static
    {
        $join = new JoinClause($type, $table);

        if (!is_string($first)) {
            $first($join);
        } else {
            if ($operator === null || $second === null) {
                throw new \InvalidArgumentException('Join operator and second column are required.');
            }

            $join->on($first, $operator, $second);
        }

        $this->joins[] = $join;

        return $this;
    }

    public function leftJoin(string $table, string|callable $first, ?string $operator = null, ?string $second = null): static
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }

    public function rightJoin(string $table, string|callable $first, ?string $operator = null, ?string $second = null): static
    {
        return $this->join($table, $first, $operator, $second, 'RIGHT');
    }

    public function crossJoin(string $table): static
    {
        $this->joins[] = new JoinClause('CROSS', $table);

        return $this;
    }

    /** @param list<mixed> $bindings */
    protected function compileJoins(array &$bindings): string
    {
        if (empty($this->joins)) {
            return '';
        }

        $sql = '';

        foreach ($this->joins as $join) {
            $sql .= ' ' . $join->type . ' JOIN ' . $this->wrapTable($join->table);

            $conditions = [];

            foreach ($join->conditions as $cond) {
                if ($cond['type'] === 'on') {
                    $conditions[] = sprintf(
                        '%s %s %s',
                        $this->wrapColumn($cond['first']),
                        $cond['operator'],
                        $this->wrapColumn($cond['second'])
                    );
                }

                if ($cond['type'] === 'where') {
                    $conditions[] = sprintf(
                        '%s %s ?',
                        $this->wrapColumn($cond['column']),
                        $cond['operator']
                    );

                    $bindings[] = $cond['value'];
                }
            }

            if ($conditions) {
                $sql .= ' ON ' . implode(' AND ', $conditions);
            }
        }

        return $sql;
    }

    // ------------------------------------------------------
    // GROUP BY / HAVING
    // ------------------------------------------------------

    public function groupBy(string|array $columns): static
    {
        foreach ((array) $columns as $column) {
            $this->groups[] = $column;
        }

        return $this;
    }

    public function having(string $column, string $operator, mixed $value, string $boolean = 'AND'): static
    {
        $this->havings[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => strtoupper($boolean),
        ];

        return $this;
    }

    public function orHaving(string $column, string $operator, mixed $value): static
    {
        return $this->having($column, $operator, $value, 'OR');
    }

    public function havingRaw(string $expression, string $boolean = 'AND'): static
    {
        $this->havings[] = [
            'type' => 'raw',
            'sql' => $expression,
            'boolean' => strtoupper($boolean),
        ];

        return $this;
    }

    // ------------------------------------------------------
    // ORDER / LIMIT / OFFSET
    // ------------------------------------------------------

    public function orderBy(string $column, string $direction = 'asc'): static
    {
        $this->orders[] = [
            'type' => 'basic',
            'column' => $column,
            'direction' => strtolower($direction) === 'desc' ? 'DESC' : 'ASC',
        ];

        return $this;
    }

    public function orderByRaw(string $expression): static
    {
        $this->orders[] = [
            'raw' => $expression,
            'type' => 'raw'
        ];

        return $this;
    }

    public function limit(int $value): static
    {
        $this->limit = max(0, $value);

        return $this;
    }

    public function offset(int $value): static
    {
        $this->offset = max(0, $value);

        return $this;
    }

    // ------------------------------------------------------
    // FETCHING
    // ------------------------------------------------------

    /**
     * @return list<array<string, mixed>>
     */
    public function get(): array
    {
        [$sql, $bindings] = $this->compileSelect();

        return $this->connection->select($sql, $bindings);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function first(): ?array
    {
        $clone = clone $this;
        $clone->limit ??= 1;

        [$sql, $bindings] = $clone->compileSelect();

        return $this->connection->selectOne($sql, $bindings);
    }

    public function toSql(): string
    {
        return $this->compileSelect()[0];
    }

    /**
     * @return array<int, mixed>
     */
    public function getBindings(): array
    {
        return $this->compileSelect()[1];
    }

    // ------------------------------------------------------
    // INSERT
    // ------------------------------------------------------

    public function insert(array $values): bool
    {
        [$sql, $bindings] = $this->compileInsert($values);

        return $this->connection->insert($sql, $bindings);
    }

    public function insertGetId(array $values): int
    {
        [$sql, $bindings] = $this->compileInsert($values);

        $this->connection->insert($sql, $bindings);

        return (int) $this->connection->getPdo()->lastInsertId();
    }

    /**
     * @param array<string, mixed> $values
     * @return array{string, list<mixed>}
     */
    protected function compileInsert(array $values): array
    {
        $columns = array_keys($values);
        $wrapped = implode(', ', array_map([$this, 'wrapColumn'], $columns));
        $placeholders = rtrim(str_repeat('?, ', count($values)), ', ');

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->wrapTable($this->table),
            $wrapped,
            $placeholders
        );

        return [$sql, array_values($values)];
    }

    // ------------------------------------------------------
    // UPDATE
    // ------------------------------------------------------

    public function update(array $values): int
    {
        [$sql, $bindings] = $this->compileUpdate($values);

        return $this->connection->update($sql, $bindings);
    }

    /**
     * @param array<string, mixed> $values
     * @return array{string, list<mixed>}
     */
    protected function compileUpdate(array $values): array
    {
        $set = [];
        $bindings = [];

        foreach ($values as $column => $value) {
            $set[] = $this->wrapColumn($column) . ' = ?';
            $bindings[] = $value;
        }

        $sql = 'UPDATE ' . $this->wrapTable($this->table)
            . ' SET ' . implode(', ', $set);

        $whereSql = $this->compileWhere($this->where, $bindings);

        if ($whereSql) {
            $sql .= ' WHERE ' . $whereSql;
        }

        return [$sql, $bindings];
    }

    // ------------------------------------------------------
    // DELETE
    // ------------------------------------------------------

    public function delete(): int
    {
        [$sql, $bindings] = $this->compileDelete();

        return $this->connection->delete($sql, $bindings);
    }

    /** @return array{string, list<mixed>} */
    protected function compileDelete(): array
    {
        $bindings = [];
        $sql = 'DELETE FROM ' . $this->wrapTable($this->table);

        $whereSql = $this->compileWhere($this->where, $bindings);

        if ($whereSql) {
            $sql .= ' WHERE ' . $whereSql;
        }

        return [$sql, $bindings];
    }

    // ------------------------------------------------------
    // SELECT compiler
    // ------------------------------------------------------

    /** @return array{string, list<mixed>} */
    protected function compileSelect(): array
    {
        $bindings = [];

        $sql = 'SELECT ';

        if ($this->distinct) {
            $sql .= 'DISTINCT ';
        }

        $sql .= $this->compileColumns();
        $sql .= ' FROM ' . $this->wrapTable($this->table);

        // JOINs
        $joins = $this->compileJoins($bindings);

        if ($joins) {
            $sql .= $joins;
        }

        // WHERE
        $whereSql = $this->compileWhere($this->where, $bindings);

        if ($whereSql) {
            $sql .= ' WHERE ' . $whereSql;
        }

        // GROUP BY
        if (!empty($this->groups)) {
            $cols = array_map([$this, 'wrapColumn'], $this->groups);
            $sql .= ' GROUP BY ' . implode(', ', $cols);
        }

        // HAVING
        if (!empty($this->havings)) {
            $havingParts = [];

            foreach ($this->havings as $i => $having) {
                $boolean = $i === 0 ? '' : $having['boolean'] . ' ';

                if ($having['type'] === 'raw') {
                    $havingParts[] = $boolean . $having['sql'];

                    continue;
                }

                if ($having['type'] === 'basic') {
                    $bindings[] = $having['value'];

                    $havingParts[] = sprintf(
                        '%s%s %s ?',
                        $boolean,
                        $this->wrapColumn($having['column']),
                        $having['operator']
                    );
                }
            }

            if ($havingParts) {
                $sql .= ' HAVING ' . implode(' ', $havingParts);
            }
        }

        // ORDER BY
        if (!empty($this->orders)) {
            $parts = [];

            foreach ($this->orders as $order) {
                if ($order['type'] === 'raw') {
                    $parts[] = $order['raw'];
                } elseif ($order['type'] === 'basic') {
                    $parts[] = $this->wrapColumn($order['column']) . ' ' . $order['direction'];
                }
            }

            $sql .= ' ORDER BY ' . implode(', ', $parts);
        }

        // LIMIT / OFFSET
        if ($this->limit !== null) {
            $sql .= ' LIMIT ' . (int) $this->limit;
        }

        if ($this->offset !== null) {
            $sql .= ' OFFSET ' . (int) $this->offset;
        }

        return [$sql, $bindings];
    }

    protected function compileColumns(): string
    {
        $parts = [];

        foreach ($this->columns as $col) {
            if ($col instanceof RawExpression) {
                $parts[] = $col->getValue();
            } else {
                $parts[] = $this->wrapColumn($col);
            }
        }

        if (empty($parts)) {
            return '*';
        }

        return implode(', ', $parts);
    }

    // ------------------------------------------------------
    // WHERE compilation
    // ------------------------------------------------------

    /** @param list<mixed> $bindings */
    protected function compileWhere(WhereGroup $group, array &$bindings): ?string
    {
        if ($group->isEmpty()) {
            return null;
        }

        $sqlParts = [];

        foreach ($group->items as $i => $item) {
            $boolean = $i === 0 ? '' : $item['boolean'] . ' ';

            if ($item['type'] === 'condition') {
                /** @var WhereCondition $cond */
                $cond = $item['condition'];

                // RAW
                if ($cond->operator === 'RAW') {
                    if (!is_array($cond->value)
                        || !is_string($cond->value[0] ?? null)
                        || !is_array($cond->value[1] ?? null)) {
                        throw new \LogicException('Invalid raw where condition.');
                    }

                    $expr = $cond->value[0];
                    $params = $cond->value[1];

                    foreach ($params as $p) {
                        $bindings[] = $p;
                    }

                    $sqlParts[] = $boolean . '(' . $expr . ')';

                    continue;
                }

                // IS NULL / IS NOT NULL
                if ($cond->operator === 'IS NULL' || $cond->operator === 'IS NOT NULL') {
                    $sqlParts[] = $boolean . sprintf(
                        '%s %s',
                        $this->wrapColumn($cond->column),
                        $cond->operator
                    );

                    continue;
                }

                // BETWEEN / NOT BETWEEN
                if ($cond->operator === 'BETWEEN' || $cond->operator === 'NOT BETWEEN') {
                    if (!is_array($cond->value) || count($cond->value) !== 2) {
                        throw new \LogicException('Between condition requires exactly two values.');
                    }

                    $range = array_values($cond->value);
                    $bindings[] = $range[0];
                    $bindings[] = $range[1];

                    $sqlParts[] = $boolean . sprintf(
                        '%s %s ? AND ?',
                        $this->wrapColumn($cond->column),
                        $cond->operator
                    );

                    continue;
                }

                // IN / NOT IN
                if ($cond->isList && ($cond->operator === 'IN' || $cond->operator === 'NOT IN')) {
                    if (!is_array($cond->value)) {
                        throw new \LogicException('IN condition requires an array.');
                    }

                    $placeholders = implode(', ', array_fill(0, count($cond->value), '?'));

                    foreach ($cond->value as $v) {
                        $bindings[] = $v;
                    }

                    $sqlParts[] = $boolean . sprintf(
                        '%s %s (%s)',
                        $this->wrapColumn($cond->column),
                        $cond->operator,
                        $placeholders
                    );

                    continue;
                }

                // Normal condition
                $bindings[] = $cond->value;

                $sqlParts[] = $boolean . sprintf(
                    '%s %s ?',
                    $this->wrapColumn($cond->column),
                    $cond->operator
                );
            }

            if ($item['type'] === 'group') {
                $nested = $this->compileWhere($item['group'], $bindings);

                if ($nested !== null) {
                    $sqlParts[] = $boolean . '(' . $nested . ')';
                }
            }
        }

        return implode(' ', $sqlParts);
    }

    // ------------------------------------------------------
    // Aggregates
    // ------------------------------------------------------

    protected function aggregate(string $function, string $column = '*'): mixed
    {
        $alias = '_aggregate';

        // Clone the builder so as not to spoil the original
        $clone = clone $this;

        $clone->columns = [new RawExpression(
            sprintf('%s(%s) as %s', $function, $clone->wrapColumn($column), $alias)
        )];

        $clone->orders = [];
        $clone->limit = null;
        $clone->offset = null;

        [$sql, $bindings] = $clone->compileSelect();

        $row = $this->connection->selectOne($sql, $bindings);

        if (!$row) {
            return null;
        }

        return $row[$alias] ?? null;
    }

    public function count(string $column = '*'): int
    {
        return (int) $this->numericAggregate('COUNT', $column);
    }

    public function sum(string $column): float|int
    {
        return $this->numericAggregate('SUM', $column);
    }

    public function avg(string $column): float|int
    {
        return $this->numericAggregate('AVG', $column);
    }

    public function min(string $column): float|int
    {
        return $this->numericAggregate('MIN', $column);
    }

    public function max(string $column): float|int
    {
        return $this->numericAggregate('MAX', $column);
    }

    protected function numericAggregate(string $function, string $column): float|int
    {
        $value = $this->aggregate($function, $column);

        if (is_int($value) || is_float($value)) {
            return $value;
        }
        if (is_string($value) && is_numeric($value)) {
            return str_contains($value, '.') ? (float) $value : (int) $value;
        }

        return 0;
    }

    public function exists(): bool
    {
        $clone = clone $this;

        $clone->columns = [new RawExpression('1')];
        $clone->limit = 1;

        [$sql, $bindings] = $clone->compileSelect();

        return (bool) $this->connection->selectOne($sql, $bindings);
    }

    public function doesntExist(): bool
    {
        return !$this->exists();
    }

    // ------------------------------------------------------
    // Helpers: value / pluck / pagination
    // ------------------------------------------------------

    public function value(string $column): mixed
    {
        [$selectColumn, $resultKey] = $this->normalizeSelectAndKey($column);

        $result = $this->select($selectColumn)->first();

        if (!$result) {
            return null;
        }

        return $result[$resultKey] ?? null;
    }

    /**
     * @return array<int, mixed>|array<string, mixed>
     */
    public function pluck(string $column, ?string $key = null): array
    {
        [$selectColumn, $valueKey] = $this->normalizeSelectAndKey($column);
        $selects = [$selectColumn];
        $keyName = null;

        if ($key !== null) {
            [$selectKey, $keyName] = $this->normalizeSelectAndKey($key);
            $selects[] = $selectKey;
        }

        $this->select($selects);

        $rows = $this->get();
        $result = [];

        foreach ($rows as $row) {
            if ($keyName !== null) {
                $resultKey = $row[$keyName] ?? null;
                if ((is_int($resultKey) || is_string($resultKey)) && array_key_exists($valueKey, $row)) {
                    $result[$resultKey] = $row[$valueKey];
                }
            } else {
                if (array_key_exists($valueKey, $row)) {
                    $result[] = $row[$valueKey];
                }
            }
        }

        return $result;
    }

    public function forPage(int $page, int $perPage): static
    {
        $page = max(1, $page);

        $this->limit($perPage);
        $this->offset(($page - 1) * $perPage);

        return $this;
    }

    /**
     * @return array{data: array<int, array<string, mixed>>, per_page: int, current_page: int, next_page: int|null, prev_page: int|null}
     */
    public function simplePaginate(int $perPage = 15, int $page = 1): array
    {
        $page = max(1, $page);

        $clone = clone $this;

        $clone->limit($perPage + 1);
        $clone->offset(($page - 1) * $perPage);

        $rows = $clone->get();

        $hasMore = count($rows) > $perPage;

        $data = array_slice($rows, 0, $perPage);

        return [
            'data' => $data,
            'per_page' => $perPage,
            'current_page' => $page,
            'next_page' => $hasMore ? $page + 1 : null,
            'prev_page' => $page > 1 ? $page - 1 : null,
        ];
    }

    // ------------------------------------------------------
    // Low-level helpers
    // ------------------------------------------------------

    protected function wrapTable(string $table): string
    {
        return '`' . str_replace('`', '``', $table) . '`';
    }

    protected function wrapColumn(string $column): string
    {
        [$expr, $alias] = $this->splitAlias($column);

        if ($alias !== null) {
            return $this->wrapExpression($expr) . ' AS ' . $this->wrapIdentifier($alias);
        }

        return $this->wrapExpression($expr);
    }

    /**
     * @return array{0: string, 1: string|null}
     */
    protected function splitAlias(string $value): array
    {
        if (preg_match('/\s+as\s+/i', $value)) {
            $parts = preg_split('/\s+as\s+/i', $value, 2);

            if ($parts === false || count($parts) !== 2) {
                return [$value, null];
            }

            return [trim($parts[0]), trim($parts[1])];
        }

        if (preg_match('/^(.+)\s+([A-Za-z_][A-Za-z0-9_]*)$/', trim($value), $matches)) {
            return [trim($matches[1]), $matches[2]];
        }

        return [$value, null];
    }

    protected function wrapExpression(string $expr): string
    {
        if ($expr === '*') {
            return '*';
        }

        if (str_contains($expr, '(') || str_contains($expr, ')')) {
            return $expr;
        }

        if (str_contains($expr, '.')) {
            [$table, $col] = explode('.', $expr, 2);

            if ($col === '*') {
                return $this->wrapTable($table) . '.*';
            }

            return $this->wrapTable($table) . '.' . $this->wrapIdentifier($col);
        }

        return $this->wrapIdentifier($expr);
    }

    protected function wrapIdentifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function normalizeSelectAndKey(string $column): array
    {
        [$expr, $alias] = $this->splitAlias($column);
        $key = $alias ?? $this->extractColumnKey($expr);

        return [$column, $key];
    }

    protected function extractColumnKey(string $column): string
    {
        if (str_contains($column, '.')) {
            $parts = explode('.', $column);

            return $parts[count($parts) - 1];
        }

        return $column;
    }

    protected function newScopedBuilder(WhereGroup $group): static
    {
        $clone = clone $this;
        $clone->where = $group;

        return $clone;
    }
}
