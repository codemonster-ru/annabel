<?php

namespace Codemonster\Annabel\Http\Exceptions;

class ForbiddenHttpException extends HttpException
{
    public function __construct(string $message = 'Forbidden')
    {
        parent::__construct(403, $message);
    }
}
