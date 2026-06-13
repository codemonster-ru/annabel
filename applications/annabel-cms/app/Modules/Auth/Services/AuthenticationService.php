<?php

namespace Codemonster\Cms\Modules\Auth\Services;

use Codemonster\Cms\Modules\Auth\Contracts\AuthenticatedUser;
use Codemonster\Cms\Modules\Auth\Contracts\AuthenticatorInterface;
use Codemonster\Cms\Modules\Auth\Models\User;

class AuthenticationService implements AuthenticatorInterface
{
    public function attempt(string $email, string $password): ?AuthenticatedUser
    {
        $user = User::findByEmail(trim($email));

        if (!$user || !password_verify($password, (string) $user->password)) {
            return null;
        }

        return new AuthenticatedUser(
            $user->id,
            (string) $user->email,
            $user->roleNames(),
        );
    }
}
