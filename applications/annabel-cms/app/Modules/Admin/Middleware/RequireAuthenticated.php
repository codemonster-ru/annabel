<?php

namespace Codemonster\Cms\Modules\Admin\Middleware;

use Codemonster\Cms\Modules\Auth\Contracts\UserSessionInterface;
use Codemonster\Http\Request;
use Codemonster\Http\Response;

class RequireAuthenticated
{
    public function __construct(
        private UserSessionInterface $users,
    ) {
    }

    public function handle(Request $request, callable $next): Response
    {
        if ($this->users->current(true) === null) {
            return $request->wantsJson()
                ? Response::json(['message' => 'Unauthenticated'], 401)
                : Response::redirect('/admin/login');
        }

        $response = $next($request);

        return $response instanceof Response
            ? $response
            : new Response((string) $response);
    }
}
