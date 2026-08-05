<?php

namespace Codemonster\Cms\Modules\Auth\Contracts;

use Codemonster\Cms\Modules\Auth\Models\User;

interface AuthorizationInterface
{
    public function allows(AuthenticatedUser|User $user, string $ability, ?object $subject = null): bool;

    public function denies(AuthenticatedUser|User $user, string $ability, ?object $subject = null): bool;
}
