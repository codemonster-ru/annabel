<?php

namespace Codemonster\Annabel\Http\Exceptions;

use RuntimeException;
use Throwable;

class HttpException extends RuntimeException
{
    /**
     * @param array<string, string|list<string>> $headers
     */
    public function __construct(
        protected int $statusCode,
        string $message = '',
        protected array $headers = [],
        ?Throwable $previous = null,
    ) {
        if ($statusCode < 400 || $statusCode > 599) {
            throw new \InvalidArgumentException('HTTP exception status must be between 400 and 599.');
        }

        parent::__construct($message !== '' ? $message : "HTTP {$statusCode}", $statusCode, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return array<string, string|list<string>>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }
}
