<?php

namespace Codemonster\Cms\Modules\Auth\Contracts;

interface UserSessionInterface
{
    public function current(bool $forceRefresh = false): ?AuthenticatedUser;

    public function login(AuthenticatedUser $user): void;

    public function logout(): void;

    public function hasRole(string $role, bool $strict = false): bool;
}
