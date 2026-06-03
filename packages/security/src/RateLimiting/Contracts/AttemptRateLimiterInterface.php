<?php

namespace Codemonster\Security\RateLimiting\Contracts;

interface AttemptRateLimiterInterface extends RateLimiterInterface
{
    /**
     * @return array{attempts:int, remaining:int, limited:bool, retry_after:int}
     */
    public function attempt(string $key, int $maxAttempts, int $decaySeconds): array;
}
