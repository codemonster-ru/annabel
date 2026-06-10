<?php

namespace Codemonster\Security\RateLimiting\Storage;

interface ThrottleStorageInterface
{
    /** @return array{attempts: int, expires_at: int}|null */
    public function get(string $key): ?array;

    /** @param array{attempts: int, expires_at: int} $value */
    public function put(string $key, array $value): void;

    public function forget(string $key): void;
}
