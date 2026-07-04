<?php

namespace Codemonster\Cms\Modules\Auth\Services;

use Codemonster\Cms\Modules\Auth\Models\User;

class PasswordResetTokenService
{
    public function issue(User $user, int $ttlSeconds): string
    {
        $token = bin2hex(random_bytes(32));
        $now = date('Y-m-d H:i:s');
        $expiresAt = date('Y-m-d H:i:s', time() + $ttlSeconds);

        transaction(function () use ($user, $token, $now, $expiresAt): void {
            $this->deleteForUser((int) $user->id);

            db()->table('password_reset_tokens')->insert([
                'user_id' => $user->id,
                'token_hash' => hash('sha256', $token),
                'expires_at' => $expiresAt,
                'created_at' => $now,
            ]);
        });

        return $token;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function validRecord(string $token): ?array
    {
        $this->deleteExpired();

        $record = db()->table('password_reset_tokens')
            ->where('token_hash', hash('sha256', $token))
            ->first();

        if (!is_array($record)) {
            return null;
        }

        $expiresAt = $record['expires_at'] ?? null;

        if (!is_string($expiresAt) || strtotime($expiresAt) === false || strtotime($expiresAt) < time()) {
            return null;
        }

        return $record;
    }

    public function deleteForUser(int $userId): void
    {
        if ($userId <= 0) {
            return;
        }

        db()->table('password_reset_tokens')
            ->where('user_id', $userId)
            ->delete();
    }

    public function deleteExpired(): void
    {
        db()->table('password_reset_tokens')
            ->where('expires_at', '<=', date('Y-m-d H:i:s'))
            ->delete();
    }
}
