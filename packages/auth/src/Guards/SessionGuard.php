<?php

namespace Codemonster\Auth\Guards;

use Codemonster\Auth\Contracts\AuthenticatableInterface;
use Codemonster\Auth\Contracts\GuardInterface;
use Codemonster\Auth\Contracts\UserProviderInterface;
use Codemonster\Session\Store;

class SessionGuard implements GuardInterface
{
    protected ?AuthenticatableInterface $user = null;
    protected bool $resolved = false;

    public function __construct(
        protected UserProviderInterface $provider,
        protected Store $session,
        protected string $sessionKey = 'auth.user_id',
    ) {
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function guest(): bool
    {
        return !$this->check();
    }

    public function user(): ?AuthenticatableInterface
    {
        if ($this->resolved) {
            return $this->user;
        }

        $this->resolved = true;
        $identifier = $this->session->get($this->sessionKey);

        if (is_int($identifier) || is_string($identifier)) {
            $this->user = $this->provider->retrieveById($identifier);
        }

        return $this->user;
    }

    public function id(): int|string|null
    {
        return $this->user()?->getAuthIdentifier();
    }

    public function attempt(array $credentials): bool
    {
        $user = $this->provider->retrieveByCredentials($credentials);

        if ($user === null || !$this->provider->validateCredentials($user, $credentials)) {
            return false;
        }

        $this->login($user);

        return true;
    }

    public function login(AuthenticatableInterface $user, bool $regenerateSession = true): void
    {
        if ($regenerateSession) {
            $this->session->regenerateId();
        }

        $this->session->put($this->sessionKey, $user->getAuthIdentifier());
        $this->user = $user;
        $this->resolved = true;
    }

    public function logout(bool $invalidateSession = true): void
    {
        $this->session->forget($this->sessionKey);
        $this->user = null;
        $this->resolved = true;

        if ($invalidateSession) {
            $this->session->invalidate();
        }
    }

    public function validate(array $credentials): bool
    {
        $user = $this->provider->retrieveByCredentials($credentials);

        return $user !== null && $this->provider->validateCredentials($user, $credentials);
    }
}
