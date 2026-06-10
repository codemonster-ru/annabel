<?php

namespace Codemonster\Auth\Contracts;

interface UserProviderInterface
{
    public function retrieveById(int|string $identifier): ?AuthenticatableInterface;

    /** @param array<string, mixed> $credentials */
    public function retrieveByCredentials(array $credentials): ?AuthenticatableInterface;

    /** @param array<string, mixed> $credentials */
    public function validateCredentials(AuthenticatableInterface $user, array $credentials): bool;
}
