<?php

namespace Codemonster\Security\RateLimiting;

use Codemonster\Security\RateLimiting\Contracts\AttemptRateLimiterInterface;
use Codemonster\Security\RateLimiting\Storage\AtomicThrottleStorageInterface;
use Codemonster\Security\RateLimiting\Storage\ThrottleStorageInterface;

class RateLimiter implements AttemptRateLimiterInterface
{
    protected ThrottleStorageInterface $storage;
    /** @var callable(): int */
    protected $now;

    public function __construct(ThrottleStorageInterface $storage, ?callable $now = null)
    {
        $this->storage = $storage;
        $this->now = $now ?? static fn() => time();
    }

    public function tooManyAttempts(string $key, int $maxAttempts): bool
    {
        $record = $this->record($key);

        if (!$record) {
            return false;
        }

        if ($this->isExpired($record)) {
            $this->clear($key);

            return false;
        }

        return (int) ($record['attempts'] ?? 0) >= $maxAttempts;
    }

    public function hit(string $key, int $decaySeconds): int
    {
        if ($this->storage instanceof AtomicThrottleStorageInterface) {
            $record = $this->storage->increment($key, $decaySeconds, ($this->now)());

            return (int) ($record['attempts'] ?? 0);
        }

        $record = $this->record($key);
        $now = ($this->now)();

        if (!$record || $this->isExpired($record)) {
            $record = [
                'attempts' => 0,
                'expires_at' => $now + $decaySeconds,
            ];
        }

        $record['attempts'] = (int) ($record['attempts'] ?? 0) + 1;

        $this->storage->put($key, $record);

        return $record['attempts'];
    }

    /**
     * Attempt a hit and decide if the limit is exceeded in one call.
     *
     * @return array{attempts:int, remaining:int, limited:bool, retry_after:int}
     */
    public function attempt(string $key, int $maxAttempts, int $decaySeconds): array
    {
        if ($this->storage instanceof AtomicThrottleStorageInterface) {
            $now = ($this->now)();
            $record = $this->storage->increment($key, $decaySeconds, $now);

            $attempts = (int) ($record['attempts'] ?? 0);
            $expiresAt = (int) ($record['expires_at'] ?? 0);

            return [
                'attempts' => $attempts,
                'remaining' => max(0, $maxAttempts - $attempts),
                'limited' => $attempts > $maxAttempts,
                'retry_after' => max(0, $expiresAt - $now),
            ];
        }

        if ($this->tooManyAttempts($key, $maxAttempts)) {
            return [
                'attempts' => $maxAttempts,
                'remaining' => 0,
                'limited' => true,
                'retry_after' => $this->availableIn($key),
            ];
        }

        $attempts = $this->hit($key, $decaySeconds);

        return [
            'attempts' => $attempts,
            'remaining' => max(0, $maxAttempts - $attempts),
            'limited' => false,
            'retry_after' => 0,
        ];
    }

    public function availableIn(string $key): int
    {
        $record = $this->record($key);

        if (!$record) {
            return 0;
        }

        $now = ($this->now)();
        $expiresAt = (int) ($record['expires_at'] ?? 0);

        return max(0, $expiresAt - $now);
    }

    public function clear(string $key): void
    {
        $this->storage->forget($key);
    }

    protected function record(string $key): ?array
    {
        $value = $this->storage->get($key);

        return is_array($value) ? $value : null;
    }

    protected function isExpired(array $record): bool
    {
        $expiresAt = (int) ($record['expires_at'] ?? 0);
        $now = ($this->now)();

        return $expiresAt !== 0 && $now >= $expiresAt;
    }
}
