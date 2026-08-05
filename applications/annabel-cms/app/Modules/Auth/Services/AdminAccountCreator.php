<?php

namespace Codemonster\Cms\Modules\Auth\Services;

use Codemonster\Cms\Modules\Auth\Models\User;

class AdminAccountCreator
{
    /**
     * @param array{username: string, email: string, password: string} $data
     */
    public function create(array $data): User
    {
        if (!User::validUsername($data['username'])) {
            throw new \InvalidArgumentException(
                'Username may contain only letters, numbers, underscores, or hyphens and must start with a letter or number.',
            );
        }

        if (User::findByUsername($data['username']) instanceof User) {
            throw new \RuntimeException('Admin username is already in use.');
        }

        if (User::findByEmail($data['email']) instanceof User) {
            throw new \RuntimeException('Admin email is already in use.');
        }

        return transaction(function () use ($data): User {
            $user = User::create([
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            ]);

            $user->assignRole('admin');

            return $user;
        });
    }
}
