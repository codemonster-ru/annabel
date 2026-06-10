<?php

namespace Codemonster\Auth\Contracts;

interface PasswordHasherInterface
{
    public function make(string $plain): string;

    public function check(string $plain, string $hash): bool;

    public function needsRehash(string $hash): bool;
}
