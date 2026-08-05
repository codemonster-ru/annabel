<?php

namespace Codemonster\Cms\Modules\Admin\Middleware;

use Codemonster\Cms\Modules\Auth\Contracts\AuthorizationInterface;
use Codemonster\Cms\Modules\Auth\Contracts\UserSessionInterface;
use Codemonster\Http\Request;
use Codemonster\Http\Response;

class RequirePermission
{
    public function __construct(
        private UserSessionInterface $users,
        private AuthorizationInterface $authorization,
    ) {
    }

    public function handle(Request $request, callable $next, ?string $permission = null): Response
    {
        if (!is_string($permission) || trim($permission) === '') {
            throw new \InvalidArgumentException('Authorization permission is required.');
        }

        $permissions = array_values(array_filter(
            array_map('trim', explode(',', $permission)),
            static fn (string $item): bool => $item !== '',
        ));

        $user = $this->users->current(true);

        if ($user === null) {
            return $request->wantsJson()
                ? Response::json(['message' => 'Unauthenticated'], 401)
                : Response::redirect('/admin/login');
        }

        $allowed = false;

        foreach ($permissions as $item) {
            if ($this->authorization->allows($user, $item)) {
                $allowed = true;
                break;
            }
        }

        if (!$allowed) {
            return $request->wantsJson()
                ? Response::json(['message' => 'Forbidden'], 403)
                : new Response('Forbidden', 403);
        }

        $response = $next($request);

        return $response instanceof Response
            ? $response
            : new Response((string) $response);
    }
}
