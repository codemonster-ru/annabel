<?php

namespace Codemonster\Cms\Modules\Auth\Middleware;

use Codemonster\Cms\Modules\Auth\Contracts\UserSessionInterface;
use Codemonster\Http\Request;
use Codemonster\Http\Response;

class GuestMiddleware
{
    public function __construct(
        private UserSessionInterface $users,
    ) {
    }

    public function handle(Request $request, callable $next): Response
    {
        if ($this->users->current()) {
            return Response::redirect('/profile');
        }

        return $next($request);
    }
}
