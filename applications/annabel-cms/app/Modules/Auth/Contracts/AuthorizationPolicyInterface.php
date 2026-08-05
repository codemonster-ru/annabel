<?php

namespace Codemonster\Cms\Modules\Auth\Contracts;

use Codemonster\Cms\Modules\Auth\Models\User;

interface AuthorizationPolicyInterface
{
    public function allows(AuthenticatedUser|User $user, string $ability, object $subject): bool;
}
