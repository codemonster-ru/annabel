<?php

namespace Codemonster\Cms\Modules\Auth\Contracts;

interface UserSessionInterface
{
    public function current(bool $forceRefresh = false): ?AuthenticatedUser;

    public function login(AuthenticatedUser $user, bool $remember = false): ?string;

    public function logout(): void;

    public function forgetRememberToken(int|string $userId): void;

    public function rememberCookieName(): string;

    public function rememberCookieLifetime(): int;

    public function hasGroup(string $group, bool $strict = false): bool;
}
