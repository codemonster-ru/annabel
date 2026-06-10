<?php

namespace Codemonster\Support\Contracts;

interface HttpStatusExceptionInterface extends \Throwable
{
    public function getStatusCode(): int;
}
