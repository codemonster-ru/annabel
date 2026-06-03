<?php

namespace Codemonster\Database\Exceptions;

use Throwable;

class QueryException extends DatabaseException
{
    protected string $sql;
    protected array $bindings;

    public function __construct(
        string $message,
        string $sql = '',
        array $bindings = [],
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);

        $this->sql = $sql;
        $this->bindings = $bindings;
    }

    public function getSql(): string
    {
        return $this->sql;
    }

    public function getBindings(): array
    {
        return $this->bindings;
    }
}
