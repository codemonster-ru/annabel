<?php

namespace Codemonster\Cms\Modules\Auth\Services;

use Codemonster\Cms\Modules\Auth\Contracts\AuthenticatedUser;
use Codemonster\Cms\Modules\Auth\Contracts\AuthenticatorInterface;
use Codemonster\Cms\Modules\Auth\Models\User;
use Psr\Clock\ClockInterface;

class AuthenticationService implements AuthenticatorInterface
{
    public function __construct(private ClockInterface $clock)
    {
    }

    public function attempt(string $login, string $password): ?AuthenticatedUser
    {
        $user = User::findByLogin(trim($login));

        if (!$user || !$user->isActiveAt($this->clock->now()) || !password_verify($password, (string) $user->password)) {
            return null;
        }

        return new AuthenticatedUser(
            $user->id,
            (string) $user->username,
            (string) $user->email,
            $user->groupNames(),
        );
    }
}
