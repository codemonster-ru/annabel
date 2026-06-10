<?php

namespace Codemonster\Auth\Contracts;

interface GuardInterface
{
    public function check(): bool;

    public function guest(): bool;

    public function user(): ?AuthenticatableInterface;

    public function id(): int|string|null;

    /** @param array<string, mixed> $credentials */
    public function attempt(array $credentials): bool;

    public function login(AuthenticatableInterface $user, bool $regenerateSession = true): void;

    public function logout(bool $invalidateSession = true): void;

    /** @param array<string, mixed> $credentials */
    public function validate(array $credentials): bool;
}
