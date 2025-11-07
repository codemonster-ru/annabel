<?php

namespace Codemonster\Annabel\Exceptions;

use Codemonster\Annabel\Contracts\ExceptionHandlerInterface;
use Codemonster\Http\Response;
use Throwable;

class DefaultExceptionHandler implements ExceptionHandlerInterface
{
    public function handle(Throwable $e): Response
    {
        $content = "Internal Server Error";

        return new Response($content, 500, ['Content-Type' => 'text/plain']);
    }
}
