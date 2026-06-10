<?php

namespace Codemonster\Auth\Hashing;

use Codemonster\Auth\Contracts\PasswordHasherInterface;

class NativePasswordHasher implements PasswordHasherInterface
{
    /** @param array<string, mixed> $options */
    public function __construct(
        protected string|int|null $algorithm = PASSWORD_DEFAULT,
        protected array $options = [],
    ) {
    }

    public function make(string $plain): string
    {
        return password_hash($plain, $this->algorithm, $this->options);
    }

    public function check(string $plain, string $hash): bool
    {
        return $hash !== '' && password_verify($plain, $hash);
    }

    public function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, $this->algorithm, $this->options);
    }
}
