<?php

namespace Codemonster\Security\Csrf;

use Codemonster\Session\Session;
use Codemonster\Session\Store;

class CsrfTokenManager
{
    protected Store $session;
    protected string $sessionKey;

    public function __construct(?Store $session = null, string $sessionKey = '_csrf_token')
    {
        $this->session = $session ?? Session::store();
        $this->sessionKey = $sessionKey;
    }

    public function token(): string
    {
        $token = $this->session->get($this->sessionKey);

        if (is_string($token) && $token !== '') {
            return $token;
        }

        return $this->regenerateToken();
    }

    public function regenerateToken(): string
    {
        $token = bin2hex(random_bytes(32));

        $this->session->put($this->sessionKey, $token);

        return $token;
    }

    public function validate(?string $providedToken): bool
    {
        if (!is_string($providedToken) || $providedToken === '') {
            return false;
        }

        $token = $this->session->get($this->sessionKey);

        if (!is_string($token) || $token === '') {
            return false;
        }

        return hash_equals($token, $providedToken);
    }

    public function sessionKey(): string
    {
        return $this->sessionKey;
    }
}
