<?php

namespace Codemonster\Annabel\Contracts;

use Throwable;
use Codemonster\Http\Response;

interface ExceptionHandlerInterface
{
    public function handle(Throwable $e): Response;
}
