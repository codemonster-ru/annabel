<?php

namespace Codemonster\Database\Query;

class RawExpression
{
    public string $expression;

    public function __construct(string $expression)
    {
        $this->expression = $expression;
    }

    public function getValue(): string
    {
        return $this->expression;
    }
}
