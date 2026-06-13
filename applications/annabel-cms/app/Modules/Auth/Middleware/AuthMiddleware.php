<?php

namespace Codemonster\Cms\Modules\Auth\Middleware;

use Codemonster\Cms\Modules\Auth\Contracts\UserSessionInterface;
use Codemonster\Http\Request;
use Codemonster\Http\Response;

class AuthMiddleware
{
    public function __construct(
        private UserSessionInterface $users,
    ) {
    }

    public function handle(Request $request, callable $next, ?string $requiredRole = null): Response
    {
        [$role, $strict] = $this->parseAccessOptions($requiredRole);
        $user = $this->users->current($strict);

        if (!$user) {
            session()->put('intended_url', $request->uri());

            return Response::redirect('/login');
        }

        if ($role && !$this->users->hasRole($role, false)) {
            abort(403);
        }

        $result = $next($request);

        if (!$result instanceof Response) {
            return new Response((string) $result);
        }

        return $result;
    }

    /**
     * @return array{0: ?string, 1: bool}
     */
    private function parseAccessOptions(?string $requiredRole): array
    {
        if ($requiredRole === null || $requiredRole === '') {
            return [null, false];
        }

        $parts = preg_split('/[|,]/', $requiredRole) ?: [];
        $parts = array_map('trim', $parts);
        $parts = array_filter($parts, static fn ($part) => $part !== '');

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
}
