<?php

namespace Codemonster\Auth\Providers;

use Codemonster\Auth\Contracts\AuthenticatableInterface;
use Codemonster\Auth\Contracts\PasswordHasherInterface;
use Codemonster\Auth\Contracts\UserProviderInterface;
use Codemonster\Auth\Hashing\NativePasswordHasher;

class ArrayUserProvider implements UserProviderInterface
{
    /** @var array<string, AuthenticatableInterface> */
    protected array $usersById = [];
    /** @var array<string, AuthenticatableInterface> */
    protected array $usersByCredential = [];

    /**
     * @param iterable<AuthenticatableInterface> $users
     */
    public function __construct(
        iterable $users,
        protected PasswordHasherInterface $hasher = new NativePasswordHasher(),
        protected string $credentialKey = 'email',
    ) {
        foreach ($users as $user) {
            $this->add($user);
        }
    }

    public function add(AuthenticatableInterface $user): void
    {
        $this->usersById[(string) $user->getAuthIdentifier()] = $user;

        if (method_exists($user, $this->credentialKey)) {
            $credential = $user->{$this->credentialKey}();

            if (is_scalar($credential)) {
                $this->usersByCredential[(string) $credential] = $user;
            }
        }
    }

    public function retrieveById(int|string $identifier): ?AuthenticatableInterface
    {
        return $this->usersById[(string) $identifier] ?? null;
    }

    public function retrieveByCredentials(array $credentials): ?AuthenticatableInterface
    {
        $credential = $credentials[$this->credentialKey] ?? null;

        if (!is_scalar($credential)) {
            return null;
        }

        return $this->usersByCredential[(string) $credential] ?? null;
    }

    public function validateCredentials(AuthenticatableInterface $user, array $credentials): bool
    {
        $password = $credentials['password'] ?? null;

        return is_string($password) && $this->hasher->check($password, $user->getAuthPassword());
    }
}
