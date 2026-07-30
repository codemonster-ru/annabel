<?php

namespace Codemonster\Cms\Modules\Admin\Services;

use Codemonster\Cms\Modules\Auth\Contracts\UserSessionInterface;
use Codemonster\Http\Response;
use Psr\Clock\ClockInterface;

class RememberCookieResponseFactory
{
    public function __construct(
        private UserSessionInterface $users,
        private ClockInterface $clock,
    ) {
    }

    public function withRememberToken(Response $response, string $value): Response
    {
        return $response->withCookie(
            $this->users->rememberCookieName(),
            $value,
            $this->options($this->clock->now()->getTimestamp() + $this->users->rememberCookieLifetime()),
        );
    }

    public function withoutRememberToken(Response $response): Response
    {
        return $response->withCookie(
            $this->users->rememberCookieName(),
            '',
            $this->options($this->clock->now()->getTimestamp() - 3600, 0),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function options(int $expiresAt, ?int $maxAge = null): array
    {
        $sessionCookie = (array) config('session.cookie', []);
        $options = [
            'expires' => $expiresAt,
            'path' => is_string($sessionCookie['path'] ?? null) ? $sessionCookie['path'] : '/',
            'secure' => (bool) ($sessionCookie['secure'] ?? false),
            'httponly' => (bool) ($sessionCookie['httponly'] ?? true),
            'samesite' => is_string($sessionCookie['samesite'] ?? null) ? $sessionCookie['samesite'] : 'Lax',
        ];

        if ($maxAge !== null) {
            $options['max_age'] = $maxAge;
        }

        return $options;
    }
}
