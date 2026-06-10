<?php

namespace Codemonster\Auth\Database;

use Codemonster\Auth\Contracts\AuthenticatableInterface;
use Codemonster\Auth\Contracts\PasswordHasherInterface;
use Codemonster\Auth\Contracts\UserProviderInterface;
use Codemonster\Auth\Hashing\NativePasswordHasher;
use Codemonster\Database\Contracts\ConnectionInterface;

class DatabaseUserProvider implements UserProviderInterface
{
    /**
     * @var (callable(array<string, mixed>): AuthenticatableInterface)|null
     */
    protected $userFactory;

    /**
     * @param (callable(array<string, mixed>): AuthenticatableInterface)|null $userFactory
     */
    public function __construct(
        protected ConnectionInterface $connection,
        protected PasswordHasherInterface $hasher = new NativePasswordHasher(),
        protected string $table = 'users',
        protected string $identifierColumn = 'id',
        protected string $passwordColumn = 'password',
        protected string $credentialKey = 'email',
        ?callable $userFactory = null,
    ) {
        $this->userFactory = $userFactory;
    }

    public function retrieveById(int|string $identifier): ?AuthenticatableInterface
    {
        return $this->userFromRow(
            $this->connection
                ->table($this->table)
                ->where($this->identifierColumn, $identifier)
                ->first(),
        );
    }

    public function retrieveByCredentials(array $credentials): ?AuthenticatableInterface
    {
        $credential = $credentials[$this->credentialKey] ?? null;

        if (!is_scalar($credential)) {
            return null;
        }

        return $this->userFromRow(
            $this->connection
                ->table($this->table)
                ->where($this->credentialKey, $credential)
                ->first(),
        );
    }

    public function validateCredentials(AuthenticatableInterface $user, array $credentials): bool
    {
        $password = $credentials['password'] ?? null;

        return is_string($password) && $this->hasher->check($password, $user->getAuthPassword());
    }

    /**
     * @param array<string, mixed>|null $row
     */
    protected function userFromRow(?array $row): ?AuthenticatableInterface
    {
        if ($row === null) {
            return null;
        }

        if ($this->userFactory !== null) {
            return ($this->userFactory)($row);
        }

        return new DatabaseUser($row, $this->identifierColumn, $this->passwordColumn);
    }
}
