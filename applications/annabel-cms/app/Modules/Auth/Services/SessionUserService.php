<?php

namespace Codemonster\Cms\Modules\Auth\Services;

use Codemonster\Cms\Modules\Auth\Contracts\AuthenticatedUser;
use Codemonster\Cms\Modules\Auth\Contracts\UserSessionInterface;
use Codemonster\Cms\Modules\Auth\Models\User;
use Codemonster\Security\Csrf\CsrfTokenManager;

class SessionUserService implements UserSessionInterface
{
    private const USER_CHECK_TTL_SECONDS = 300;

    public function __construct(
        private CsrfTokenManager $csrf,
    ) {
    }

    public function current(bool $forceRefresh = false): ?AuthenticatedUser
    {
        $session = session();
        $user = $session->get('user');

        if (!is_array($user)) {
            return null;
        }

        $lastCheck = (int) $session->get('user_checked_at', 0);

        if (!$forceRefresh && ($lastCheck + self::USER_CHECK_TTL_SECONDS) > time()) {
            $identity = $this->hydrate($user);

            if ($identity) {
                return $identity;
            }

            $this->logout();

            return null;
        }

        $dbUser = $this->findModel($user);

        if (!$dbUser) {
            $this->logout();

            return null;
        }

        $identity = $this->identity($dbUser);
        $this->store($identity, false);

        return $identity;
    }

    public function login(AuthenticatedUser $user): void
    {
        $this->store($user, true);
    }

    public function logout(): void
    {
        $session = session();

        $session->forgetMany([
            'user',
            'user_checked_at',
            'intended_url',
        ]);
        $session->regenerateId();
        $this->csrf->regenerateToken();
    }

    public function hasRole(string $role, bool $strict = false): bool
    {
        $user = $this->current($strict);

        if (!$user) {
            return false;
        }

        return $user->hasRole($role);
    }

    private function store(AuthenticatedUser $user, bool $regenerateSession): void
    {
        $session = session();

        if ($regenerateSession) {
            $session->regenerateId();
        }

        $session->put('user', $user->toArray());
        $session->put('user_checked_at', time());

        if ($regenerateSession) {
            $this->csrf->regenerateToken();
        }
    }

    /**
     * @param array<string, mixed> $user
     */
    private function findModel(array $user): ?User
    {
        $userId = $user['id'] ?? null;

        if (!is_scalar($userId)) {
            return null;
        }

        return User::find($userId);
    }

    private function identity(User $user): AuthenticatedUser
    {
        return new AuthenticatedUser(
            $user->id,
            (string) $user->email,
            $user->roleNames(),
        );
    }

    /**
     * @param array<string, mixed> $user
     */
    private function hydrate(array $user): ?AuthenticatedUser
    {
        $id = $user['id'] ?? null;
        $email = $user['email'] ?? null;
        $roles = $user['roles'] ?? null;

        if ((!is_int($id) && !is_string($id)) || !is_string($email) || !is_array($roles)) {
            return null;
        }

        return new AuthenticatedUser(
            $id,
            $email,
            array_values(array_filter($roles, 'is_string')),
        );
    }
}
