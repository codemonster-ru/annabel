<?php

namespace Codemonster\Database\Query;

class JoinClause
{
    public string $type;
    public string $table;

    /**
     * @var list<
     *     array{type: 'on', first: string, operator: string, second: string}
     *     |array{type: 'where', column: string, operator: string, value: mixed}
     * >
     */
    public array $conditions = [];

    public function __construct(string $type, string $table)
    {
        $this->type = strtoupper($type);
        $this->table = $table;
    }

    public function on(string $first, string $operator, string $second): static
    {
        $this->conditions[] = [
            'type' => 'on',
            'first' => $first,
            'operator' => $operator,
            'second' => $second,
        ];

        return $this;
    }

    public function where(string $column, string $operator, mixed $value): static
    {
        $this->conditions[] = [
            'type' => 'where',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
        ];

        return $this;
    }
}
