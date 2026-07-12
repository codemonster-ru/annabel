<?php

namespace Codemonster\Cms\Modules\Auth\Contracts;

final class AuthenticatedUser
{
    /**
     * @param array<int, string> $roles
     */
    public function __construct(
        public readonly int|string $id,
        public readonly string $username,
        public readonly string $email,
        public readonly array $roles,
    ) {
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles, true);
    }

    /**
     * @return array{id: int|string, username: string, email: string, roles: array<int, string>}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'roles' => $this->roles,
        ];
    }
}
