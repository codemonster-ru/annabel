<?php

namespace Codemonster\Security\RateLimiting\Storage;

interface AtomicThrottleStorageInterface extends ThrottleStorageInterface
{
    /**
     * Atomically increment attempts and set expiry for the key.
     *
     * @return array{attempts:int, expires_at:int}
     */
    public function increment(string $key, int $decaySeconds, int $now): array;
}
