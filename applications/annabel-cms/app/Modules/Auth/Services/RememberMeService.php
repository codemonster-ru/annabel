<?php

namespace Codemonster\Cms\Modules\Auth\Services;

use Codemonster\Cms\Modules\Auth\Models\User;

class RememberMeService
{
    public function issue(User $user): string
    {
        $token = bin2hex(random_bytes(32));
        $user->remember_token = hash('sha256', $token);
        $user->save();

        return $user->id . '|' . $token;
    }

    public function consume(?string $cookie): ?User
    {
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

        return $user;
    }

    public function forget(int|string $userId): void
    {
        $user = User::find($userId);

        if (!$user instanceof User) {
            return;
        }

        $user->remember_token = null;
        $user->save();
    }
}
