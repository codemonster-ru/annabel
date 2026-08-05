<?php

namespace Codemonster\Cms\Modules\Auth\Services;

use Codemonster\Cms\Modules\Auth\Contracts\AuthenticatedUser;
use Codemonster\Cms\Modules\Auth\Contracts\AuthorizationInterface;
use Codemonster\Cms\Modules\Auth\Contracts\AuthorizationPolicyInterface;
use Codemonster\Cms\Modules\Auth\Models\User;

final class AuthorizationService implements AuthorizationInterface
{
    /** @var array<class-string, class-string<AuthorizationPolicyInterface>> */
    private array $policies = [];

    /** @param class-string $subject @param class-string<AuthorizationPolicyInterface> $policy */
    public function registerPolicy(string $subject, string $policy): void
    {
        $this->policies[$subject] = $policy;
    }

    public function allows(AuthenticatedUser|User $user, string $ability, ?object $subject = null): bool
    {
        if ($ability === '') {
            return false;
        }

        if ($user->hasRole('admin')) {
            return true;
        }

        if ($subject === null) {
            return $user->hasPermission($ability);
        }

        $policyClass = $this->policies[$subject::class] ?? null;
        if ($policyClass === null) {
            return false;
        }

        $policy = app($policyClass);
        if (!$policy instanceof AuthorizationPolicyInterface) {
            throw new \RuntimeException("Invalid authorization policy: {$policyClass}");
        }

        return $policy->allows($user, $ability, $subject);
    }

    public function denies(AuthenticatedUser|User $user, string $ability, ?object $subject = null): bool
    {
        return !$this->allows($user, $ability, $subject);
    }
}
