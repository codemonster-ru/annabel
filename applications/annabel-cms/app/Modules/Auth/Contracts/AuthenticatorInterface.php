<?php

namespace Codemonster\Cms\Modules\Auth\Contracts;

interface AuthenticatorInterface
{
    public function attempt(string $login, string $password): ?AuthenticatedUser;
}
