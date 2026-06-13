<?php

namespace Codemonster\Cms\Modules\Auth\Contracts;

interface AuthenticatorInterface
{
    public function attempt(string $email, string $password): ?AuthenticatedUser;
}
