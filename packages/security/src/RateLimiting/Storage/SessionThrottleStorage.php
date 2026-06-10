<?php

namespace Codemonster\Security\RateLimiting\Storage;

use Codemonster\Session\Session;
use Codemonster\Session\Store;

class SessionThrottleStorage implements ThrottleStorageInterface
{
    protected Store $session;
    protected string $prefix;

    public function __construct(?Store $session = null, string $prefix = 'throttle:')
    {
        $this->session = $session ?? Session::store();
        $this->prefix = $prefix;
    }

    public function get(string $key): ?array
    {
        $value = $this->session->get($this->prefix . $key);

        if (!is_array($value)) {
            return null;
        }

        $attempts = $value['attempts'] ?? null;
        $expiresAt = $value['expires_at'] ?? null;

        return is_int($attempts) && is_int($expiresAt)
            ? ['attempts' => $attempts, 'expires_at' => $expiresAt]
            : null;
    }

    public function put(string $key, array $value): void
    {
        $this->session->put($this->prefix . $key, $value);
    }

    public function forget(string $key): void
    {
        $this->session->forget($this->prefix . $key);
    }
}
