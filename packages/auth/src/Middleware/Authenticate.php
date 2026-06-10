<?php

namespace Codemonster\Auth\Middleware;

use Codemonster\Auth\Contracts\GuardInterface;
use Codemonster\Http\Request;
use Codemonster\Http\Response;

class Authenticate
{
    public function __construct(
        protected GuardInterface $guard,
        protected ?string $redirectTo = null,
    ) {
    }

    public function handle(Request $request, callable $next): mixed
    {
        if ($this->guard->check()) {
            return $next($request);
        }

        if ($this->redirectTo !== null && !$request->wantsJson()) {
            return Response::redirect($this->redirectTo);
        }

        return Response::json(['message' => 'Unauthenticated.'], 401);
    }
}
