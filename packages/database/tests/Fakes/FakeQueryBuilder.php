<?php

declare(strict_types=1);

namespace Codemonster\Database\Tests\Fakes;

use Codemonster\Database\Query\JoinClause;
use Codemonster\Database\Query\QueryBuilder;

/**
 * @phpstan-type WhereClause array{
 *     column: string,
 *     operator: mixed,
 *     value?: mixed,
 *     boolean: string,
 *     type: 'basic'|'null'|'in'|'not_null'
 * }
 */
class FakeQueryBuilder extends QueryBuilder
{
    public FakeConnection $fake;
    /** @var list<WhereClause> */
    protected array $wheres = [];
    /** @var list<JoinClause> */
    protected array $joins = [];
    protected ?int $limit = null;
    protected ?int $offset = null;

    public function __construct(FakeConnection $fake, string $table)
    {
        $this->fake = $fake;
        parent::__construct($fake, $table);
    }

    public function select(string|array ...$columns): static
    {
        // selection is ignored in fake builder (we always return full rows)
        return $this;
    }

    public function where(string|callable $column, mixed $operator = null, mixed $value = null, string $boolean = 'AND'): static
    {
        if (is_callable($column)) {
            // not needed for fake tests
            return $this;
        }

        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => strtoupper($boolean),
            'type' => 'basic',
        ];

        return $this;
    }

    public function whereNull(string $column, string $boolean = 'AND'): static
    {
        $this->wheres[] = [
            'column' => $column,
            'operator' => 'NULL',
            'boolean' => strtoupper($boolean),
            'type' => 'null',
        ];

        return $this;
    }

    public function whereIn(string $column, array $values, string $boolean = 'AND'): static
    {
        $this->wheres[] = [
            'column' => $column,
            'operator' => 'IN',
            'value' => $values,
            'boolean' => strtoupper($boolean),
            'type' => 'in',
        ];

        return $this;
    }

    public function whereNotNull(string $column, string $boolean = 'AND'): static
    {
        $this->wheres[] = [
            'column' => $column,
            'operator' => 'NOT_NULL',
            'boolean' => strtoupper($boolean),
            'type' => 'not_null',
        ];

        return $this;
    }

    public function join(string $table, string|callable $first, ?string $operator = null, ?string $second = null, string $type = 'INNER'): static
    {
        if (is_callable($first)) {
            return $this;
        }

        $join = new JoinClause($type, $table);
        $join->on($first, $operator ?? '=', $second ?? '');
        $this->joins[] = $join;

        return $this;
    }

    /** @return list<array<string, mixed>> */
    public function get(): array
    {
        $this->fake->tableReads[] = $this->table;

        if (!empty($this->joins)) {
            return $this->runJoinQuery();
        }

        $rows = $this->tableRows($this->table);

        $rows = array_values(array_filter($rows, fn (array $row): bool => $this->matchesWhere($row)));

        if ($this->offset !== null) {
            $rows = array_slice($rows, $this->offset);
        }

        if ($this->limit !== null) {
            $rows = array_slice($rows, 0, $this->limit);
        }

        return array_values($rows);
    }

    /** @return array<string, mixed>|null */
    public function first(): ?array
    {
        $rows = $this->get();

        return $rows[0] ?? null;
    }

    /** @param array<string, mixed> $values */
    public function insert(array $values): bool
    {
        $this->fake->tables[$this->table][] = $values;

        return true;
    }

    /** @param array<string, mixed> $values */
    public function insertGetId(array $values, mixed $sequence = null): int
    {
        $current = $this->tableRows($this->table);
        $lastId = $current[array_key_last($current)]['id'] ?? count($current);
        $last = is_int($lastId) || is_string($lastId) ? (int) $lastId : 0;

        $id = $last + 1;
        $values['id'] = $id;

        $this->fake->tables[$this->table][] = $values;

        return $id;
    }

    /** @param array<string, mixed> $values */
    public function update(array $values): int
    {
        $updated = 0;
        $rows = $this->tableRows($this->table);

        foreach ($rows as &$row) {
            if ($this->matchesWhere($row)) {
                $row = array_merge($row, $values);
                $updated++;
            }
        }
        unset($row);

        $this->fake->tables[$this->table] = $rows;

        return $updated;
    }

    public function delete(): int
    {
        $deleted = 0;
        $rows = $this->tableRows($this->table);

        foreach ($rows as $idx => $row) {
            if ($this->matchesWhere($row)) {
                unset($rows[$idx]);

                $deleted++;
            }
        }

        /** @var list<array<string, mixed>> $rows */
        $rows = array_values($rows);
        $this->fake->tables[$this->table] = $rows;

        return $deleted;
    }

    public function count(string $column = '*'): int
    {
        return count($this->get());
    }

    public function exists(): bool
    {
        return !empty($this->get());
    }

    public function limit(int $value): static
    {
        $this->limit = $value;

        return $this;
    }

    public function offset(int $value): static
    {
        $this->offset = $value;

        return $this;
    }

    /** @param array<string, mixed> $row */
    protected function matchesWhere(array $row): bool
    {
        $result = null;

        foreach ($this->wheres as $where) {
            $column = str_contains($where['column'], '.')
                ? explode('.', $where['column'])[1]
                : $where['column'];

            $current = match ($where['type']) {
                'null' => !array_key_exists($column, $row) || $row[$column] === null,
                'not_null' => array_key_exists($column, $row) && $row[$column] !== null,
                'in' => in_array($row[$column] ?? null, is_array($where['value'] ?? null) ? $where['value'] : [], true),
                default => ($row[$column] ?? null) == ($where['value'] ?? null),
            };

            if ($result === null) {
                $result = $current;
                continue;
            }

            if ($where['boolean'] === 'OR') {
                $result = $result || $current;
            } else {
                $result = $result && $current;
            }
        }

        return $result ?? true;
    }

    /** @return list<array<string, mixed>> */
    protected function runJoinQuery(): array
    {
        // Simplified join handling for belongsToMany (single pivot join).
        $join = $this->joins[0];

        $pivotTable = $join->table;
        $pivotRows = $this->tableRows($pivotTable);

        $filter = array_values(array_filter(
            $this->wheres,
            fn (array $w): bool => str_starts_with($w['column'], $pivotTable . '.'),
        ));

        $relatedIds = [];

        foreach ($pivotRows as $pivot) {
            if ($filter) {
                $cond = $filter[0];
                $pivotColumn = str_replace($pivotTable . '.', '', $cond['column']);

                if (($pivot[$pivotColumn] ?? null) != ($cond['value'] ?? null)) {
                    continue;
                }
            }

            $condition = $join->conditions[0] ?? null;
            $first = $condition['first'] ?? '';
            $pivotRelatedKey = str_replace($pivotTable . '.', '', $first);
            $relatedIds[] = $pivot[$pivotRelatedKey] ?? null;
        }

        $relatedTable = $this->table;
        $relatedRows = $this->tableRows($relatedTable);

        $condition = $join->conditions[0] ?? null;
        $second = $condition['second'] ?? '';
        $relatedKey = str_contains($second, '.')
            ? explode('.', $second)[1]
            : $second;

        $filtered = array_filter(
            $relatedRows,
            fn (array $row): bool => in_array($row[$relatedKey] ?? null, $relatedIds, true),
        );

        return array_values($filtered);
    }

    /** @return list<array<string, mixed>> */
    private function tableRows(string $table): array
    {
        return $this->fake->tables[$table] ?? [];
    }
}
