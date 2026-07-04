<?php

namespace Codemonster\Cms\Modules\Auth\Services;

use Codemonster\Cms\Modules\Auth\Contracts\AuthenticatedUser;
use Codemonster\Cms\Modules\Auth\Contracts\UserSessionInterface;
use Codemonster\Cms\Modules\Auth\Models\User;
use Codemonster\Http\Request;
use Codemonster\Security\Csrf\CsrfTokenManager;

class SessionUserService implements UserSessionInterface
{
    private const USER_CHECK_TTL_SECONDS = 300;
    private const REMEMBER_COOKIE = 'annabel_remember';
    private const REMEMBER_TTL_SECONDS = 2592000;

    public function __construct(
        private CsrfTokenManager $csrf,
    ) {
    }

    public function current(bool $forceRefresh = false): ?AuthenticatedUser
    {
        $session = session();
        $user = $session->get('user');

        if (!is_array($user)) {
            return $this->restoreFromRememberCookie();
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

            return $this->restoreFromRememberCookie();
        }

        $identity = $this->identity($dbUser);
        $this->store($identity, false);

        return $identity;
    }

    public function login(AuthenticatedUser $user, bool $remember = false): ?string
    {
        $this->store($user, true);

        if (!$remember) {
            return null;
        }

        $model = User::find($user->id);

        if (!$model instanceof User) {
            return null;
        }

        return $this->issueRememberToken($model);
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

    public function forgetRememberToken(int|string $userId): void
    {
        $user = User::find($userId);

        if (!$user instanceof User) {
            return;
        }

        $user->remember_token = null;
        $user->save();
    }

    public function rememberCookieName(): string
    {
        return self::REMEMBER_COOKIE;
    }

    public function rememberCookieLifetime(): int
    {
        return self::REMEMBER_TTL_SECONDS;
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

    private function issueRememberToken(User $user): string
    {
        $token = bin2hex(random_bytes(32));
        $user->remember_token = hash('sha256', $token);
        $user->save();

        return $user->id . '|' . $token;
    }

    private function restoreFromRememberCookie(): ?AuthenticatedUser
    {
        $request = app(Request::class);

        if (!$request instanceof Request) {
            return null;
        }

        $cookie = $request->getCookieParams()[self::REMEMBER_COOKIE] ?? null;

        if (!is_string($cookie) || $cookie === '') {
            return null;
        }

        [$userId, $token] = array_pad(explode('|', $cookie, 2), 2, null);

        if (!is_string($userId) || !is_string($token) || $token === '') {
            return null;
        }

        $user = User::find($userId);

        if (
            !$user instanceof User
            || !is_string($user->remember_token)
            || $user->remember_token === ''
            || !hash_equals($user->remember_token, hash('sha256', $token))
        ) {
            return null;
        }

        $identity = $this->identity($user);
        $this->store($identity, true);

        return $identity;
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
