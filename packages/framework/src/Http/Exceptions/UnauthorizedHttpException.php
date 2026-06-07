<?php

namespace Codemonster\Annabel\Http\Exceptions;

class UnauthorizedHttpException extends HttpException
{
    public function __construct(string $message = 'Unauthorized', array $headers = [])
    {
        parent::__construct(401, $message, $headers);
    }
}
