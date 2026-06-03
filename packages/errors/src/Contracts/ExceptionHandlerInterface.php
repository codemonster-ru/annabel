<?php

namespace Codemonster\Errors\Contracts;

use Throwable;
use Codemonster\Http\Response;

interface ExceptionHandlerInterface
{
    public function handle(Throwable $e): Response;
}
