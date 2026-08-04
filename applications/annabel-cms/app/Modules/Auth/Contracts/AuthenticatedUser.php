<?php

namespace Codemonster\Cms\Modules\Auth\Contracts;

final class AuthenticatedUser
{
    /**
     * @param array<int, string> $groups Group codes used for authorization.
     */
    public function __construct(
        public readonly int|string $id,
        public readonly string $username,
        public readonly string $email,
        public readonly array $groups,
    ) {
    }

    public function hasGroup(string $group): bool
    {
        return in_array($group, $this->groups, true);
    }

    /**
     * @return array{id: int|string, username: string, email: string, groups: array<int, string>}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'groups' => $this->groups,
        ];
    }
}
