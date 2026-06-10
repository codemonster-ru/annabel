<?php

namespace Codemonster\Auth\Authorization;

class AuthorizationException extends \RuntimeException
{
    public function __construct(string $ability)
    {
        parent::__construct("This action is unauthorized: {$ability}.");
    }
}
