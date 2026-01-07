<?php

namespace Codemonster\Xen\Modules\Auth\Middleware;

use Codemonster\Http\Request;
use Codemonster\Http\Response;
use Codemonster\Xen\Modules\Auth\Models\User;

class AuthMiddleware
{
    private const USER_CHECK_TTL_SECONDS = 300;

    public function handle(Request $request, callable $next, ?string $requiredRole = null): Response
    {
        $session = session();

        $user = $session->get('user');

        if (!$user) {
            $session->put('intended_url', $request->uri());

            return Response::redirect('/login');
        }

        [$role, $strict] = $this->parseAccessOptions($requiredRole);

        if (!$this->validateUserSession($session, $user, $strict)) {
            $session->put('intended_url', $request->uri());

            return Response::redirect('/login');
        }

        if ($role) {
            if ($strict) {
                $dbUser = $this->findDbUser($user);

                if (!$dbUser || !$dbUser->hasRole($role)) {
                    abort(403);
                }
            } else {
                $roles = $user['roles'] ?? null;

                if (is_array($roles)) {
                    if (!in_array($role, $roles, true)) {
                        abort(403);
                    }
                } elseif (is_string($user['role'] ?? null)) {
                    if ($user['role'] !== $role) {
                        abort(403);
                    }
                } else {
                    $dbUser = $this->findDbUser($user);

                    if (!$dbUser || !$dbUser->hasRole($role)) {
                        abort(403);
                    }
                }
            }
        }

        $result = $next($request);

        if (!$result instanceof Response) {
            return new Response((string) $result);
        }

        return $result;
    }

    private function parseAccessOptions(?string $requiredRole): array
    {
        if ($requiredRole === null || $requiredRole === '') {
            return [null, false];
        }

        $parts = preg_split('/[|,]/', $requiredRole) ?: [];
        $parts = array_map('trim', $parts);
        $parts = array_filter($parts, static fn($part) => $part !== '');

        if ($parts === []) {
            return [$requiredRole, false];
        }

        $strict = false;
        $role = null;

        foreach ($parts as $part) {
            if ($part === 'strict') {
                $strict = true;
                continue;
            }

            if ($role === null) {
                $role = $part;
            }
        }

        return [$role ?? $requiredRole, $strict];
    }

    private function validateUserSession($session, $user, bool $strict): bool
    {
        $lastCheck = (int) $session->get('user_checked_at', 0);
        $shouldCheck = $strict || ($lastCheck + self::USER_CHECK_TTL_SECONDS) <= time();

        if (!$shouldCheck) {
            return true;
        }

        $dbUser = $this->findDbUser($user);

        if (!$dbUser) {
            $session->forget('user');
            $session->forget('user_checked_at');

            return false;
        }

        $session->put('user_checked_at', time());

        return true;
    }

    private function findDbUser($user): ?User
    {
        $userId = $user['id'] ?? null;

        if (!is_scalar($userId)) {
            return null;
        }

        return User::query()
            ->where((new User())->getKeyName(), $userId)
            ->first();
    }
}
