<?php

namespace Codemonster\Cms\Modules\Admin\Middleware;

use Codemonster\Cms\Modules\Auth\Contracts\UserSessionInterface;
use Codemonster\Http\Request;
use Codemonster\Http\Response;

class RequireAdmin
{
    public function __construct(
        private UserSessionInterface $users,
    ) {
    }

    public function handle(Request $request, callable $next): Response
    {
        $user = $this->users->current(true);

        if (!$user) {
            return $request->wantsJson()
                ? Response::json(['message' => 'Unauthenticated'], 401)
                : Response::redirect('/admin');
        }

        if (!$user->hasRole('admin')) {
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
