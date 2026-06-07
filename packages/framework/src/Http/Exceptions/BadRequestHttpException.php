<?php

namespace Codemonster\Annabel\Http\Exceptions;

class BadRequestHttpException extends HttpException
{
    public function __construct(string $message = 'Bad Request')
    {
        parent::__construct(400, $message);
    }
}
