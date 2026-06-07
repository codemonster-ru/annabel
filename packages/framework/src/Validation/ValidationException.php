<?php

namespace Codemonster\Annabel\Validation;

use RuntimeException;

class ValidationException extends RuntimeException
{
    protected int $statusCode = 422;

    public function __construct(protected ValidationResult $result)
    {
        parent::__construct($result->first() ?? 'The given data was invalid.');
    }

    /**
     * @return array<string, list<string>>
     */
    public function errors(): array
    {
        return $this->result->errors();
    }

    public function result(): ValidationResult
    {
        return $this->result;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
