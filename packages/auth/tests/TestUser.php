<?php

namespace Codemonster\Auth\Tests;

use Codemonster\Auth\Contracts\AuthenticatableInterface;

class TestUser implements AuthenticatableInterface
{
    public function __construct(
        private int|string $id,
        private string $email,
        private string $password,
    ) {
    }

    public function getAuthIdentifier(): int|string
    {
        return $this->id;
    }

    public function getAuthPassword(): string
    {
        return $this->password;
    }

    public function email(): string
    {
        return $this->email;
    }
}
