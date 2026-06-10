<?php

namespace Codemonster\Auth\Contracts;

interface AuthenticatableInterface
{
    public function getAuthIdentifier(): int|string;

    public function getAuthPassword(): string;
}
