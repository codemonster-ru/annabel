<?php

namespace Codemonster\Cms\Modules\Auth\Services;

use Codemonster\Cms\Modules\Auth\Models\User;
use Codemonster\DateTime\DateTime;
use Codemonster\DateTime\InvalidDateTimeException;
use Psr\Clock\ClockInterface;

class PasswordResetTokenService
{
    public function __construct(private ClockInterface $clock)
    {
    }

    public function issue(User $user, int $ttlSeconds): string
    {
        $token = bin2hex(random_bytes(32));
        $now = DateTime::now($this->clock, 'UTC');
        $expiresAt = $now->addSeconds($ttlSeconds);

        transaction(function () use ($user, $token, $now, $expiresAt): void {
            $this->deleteForUser((int) $user->id);

            db()->table('password_reset_tokens')->insert([
                'user_id' => $user->id,
                'token_hash' => hash('sha256', $token),
                'expires_at' => $expiresAt->format(DateTime::DATABASE_FORMAT),
                'created_at' => $now->format(DateTime::DATABASE_FORMAT),
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

        if (!is_string($expiresAt)) {
            return null;
        }

        try {
            $expiration = DateTime::fromFormat(DateTime::DATABASE_FORMAT, $expiresAt, 'UTC');
        } catch (InvalidDateTimeException) {
            return null;
        }

        if (!$expiration->isAfter(DateTime::now($this->clock, 'UTC'))) {
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
            ->where(
                'expires_at',
                '<=',
                DateTime::now($this->clock, 'UTC')->format(DateTime::DATABASE_FORMAT),
            )
            ->delete();
    }
}
