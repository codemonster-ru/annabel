<?php

namespace Codemonster\Annabel\Http\Exceptions;

class MethodNotAllowedHttpException extends HttpException
{
    /**
     * @param list<string> $allowedMethods
     */
    public function __construct(array $allowedMethods, string $message = 'Method Not Allowed')
    {
        $headers = $allowedMethods === []
            ? []
            : ['Allow' => implode(', ', array_map('strtoupper', $allowedMethods))];

        parent::__construct(405, $message, $headers);
    }
}
