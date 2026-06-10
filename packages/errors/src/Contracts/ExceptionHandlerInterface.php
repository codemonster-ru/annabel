<?php

namespace Codemonster\Errors\Contracts;

use Codemonster\Http\Response;
use Throwable;

interface ExceptionHandlerInterface
{
    public function handle(Throwable $e): Response;
}
