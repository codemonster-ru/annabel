<?php

namespace Codemonster\Auth\Database;

use Codemonster\Auth\Contracts\AuthenticatableInterface;

class DatabaseUser implements AuthenticatableInterface
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        protected array $attributes,
        protected string $identifierColumn = 'id',
        protected string $passwordColumn = 'password',
    ) {
    }

    public function getAuthIdentifier(): int|string
    {
        $identifier = $this->attributes[$this->identifierColumn] ?? null;

        if (is_int($identifier) || is_string($identifier)) {
            return $identifier;
        }

        throw new \RuntimeException("Authenticated database user is missing identifier column [{$this->identifierColumn}].");
    }

    public function getAuthPassword(): string
    {
        $password = $this->attributes[$this->passwordColumn] ?? null;

        if (is_string($password)) {
            return $password;
        }

        throw new \RuntimeException("Authenticated database user is missing password column [{$this->passwordColumn}].");
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    /** @return array<string, mixed> */
    public function attributes(): array
    {
        return $this->attributes;
    }
}
