<?php

namespace Codemonster\Database\Query;

class WhereCondition
{
    public string $column;
    public string $operator;
    public mixed $value;
    public string $boolean;
    public bool $isList = false;

    public function __construct(
        string $column,
        string $operator,
        mixed $value,
        string $boolean = 'AND',
        bool $isList = false,
    ) {
        $this->column = $column;
        $this->operator = $operator;
        $this->value = $value;
        $this->boolean = strtoupper($boolean);
        $this->isList = $isList;
    }
}
