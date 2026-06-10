<?php

namespace Codemonster\Database\Tests\Fakes;

use Codemonster\Database\Query\QueryBuilder;

class FakeQueryBuilder extends QueryBuilder
{
    public FakeConnection $fake;
    protected array $wheres = [];
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

        $this->joins[] = [
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second,
            'type' => strtoupper($type),
        ];

        return $this;
    }

    public function get(): array
    {
        $this->fake->tableReads[] = $this->table;

        if (!empty($this->joins)) {
            return $this->runJoinQuery();
        }

        $rows = $this->fake->tables[$this->table] ?? [];

        $rows = array_values(array_filter($rows, fn ($row) => $this->matchesWhere($row)));

        if ($this->offset !== null) {
            $rows = array_slice($rows, $this->offset);
        }

        if ($this->limit !== null) {
            $rows = array_slice($rows, 0, $this->limit);
        }

        return array_map(fn ($row) => (array) $row, $rows);
    }

    public function first(): ?array
    {
        $rows = $this->get();

        return $rows[0] ?? null;
    }

    public function insert(array $values): bool
    {
        $this->fake->tables[$this->table][] = $values;

        return true;
    }

    public function insertGetId(array $values, $sequence = null): int
    {
        $current = $this->fake->tables[$this->table] ?? [];
        $last = empty($current) ? 0 : ((int) ($current[array_key_last($current)]['id'] ?? count($current)));

        $values['id'] = $last + 1;

        $this->fake->tables[$this->table][] = $values;

        return $values['id'];
    }

    public function update(array $values): int
    {
        $updated = 0;

        foreach ($this->fake->tables[$this->table] as &$row) {
            if ($this->matchesWhere($row)) {
                $row = array_merge($row, $values);
                $updated++;
            }
        }

        return $updated;
    }

    public function delete(): int
    {
        $deleted = 0;

        foreach ($this->fake->tables[$this->table] as $idx => $row) {
            if ($this->matchesWhere($row)) {
                unset($this->fake->tables[$this->table][$idx]);

                $deleted++;
            }
        }

        $this->fake->tables[$this->table] = array_values($this->fake->tables[$this->table] ?? []);

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
                'in' => in_array($row[$column] ?? null, $where['value'], true),
                default => ($row[$column] ?? null) == $where['value'],
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

    protected function runJoinQuery(): array
    {
        // Simplified join handling for belongsToMany (single pivot join).
        $join = $this->joins[0];

        $pivotTable = $join['table'];
        $pivotRows = $this->fake->tables[$pivotTable] ?? [];

        $filter = array_values(array_filter(
            $this->wheres,
            fn ($w) => str_starts_with($w['column'], $pivotTable . '.'),
        ));

        $relatedIds = [];

        foreach ($pivotRows as $pivot) {
            if ($filter) {
                $cond = $filter[0];
                $pivotColumn = str_replace($pivotTable . '.', '', $cond['column']);

                if (($pivot[$pivotColumn] ?? null) != $cond['value']) {
                    continue;
                }
            }

            $pivotRelatedKey = str_replace($pivotTable . '.', '', $join['first']);
            $relatedIds[] = $pivot[$pivotRelatedKey] ?? null;
        }

        $relatedTable = $this->table;
        $relatedRows = $this->fake->tables[$relatedTable] ?? [];

        $relatedKey = str_contains($join['second'], '.')
            ? explode('.', $join['second'])[1]
            : $join['second'];

        $filtered = array_filter(
            $relatedRows,
            fn ($row) => in_array($row[$relatedKey] ?? null, $relatedIds, true),
        );

        return array_map(fn ($row) => (array) $row, array_values($filtered));
    }
}
