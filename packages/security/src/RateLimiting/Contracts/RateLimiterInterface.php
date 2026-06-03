<?php

namespace Codemonster\Security\RateLimiting\Contracts;

interface RateLimiterInterface
{
    public function tooManyAttempts(string $key, int $maxAttempts): bool;
    public function hit(string $key, int $decaySeconds): int;
    public function availableIn(string $key): int;
    public function clear(string $key): void;
}

