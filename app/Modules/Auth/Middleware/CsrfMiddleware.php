<?php

namespace Codemonster\Xen\Modules\Auth\Middleware;

use Codemonster\Http\Request;
use Codemonster\Http\Response;

class CsrfMiddleware
{
    public function handle(Request $request, callable $next): Response
    {
        if (!$request->is('POST')) {
            return $next($request);
        }

        $token = (string)$request->input('_token', '');
        $sessionToken = (string)session()->get('_csrf_token', '');

        if ($token === '' || $sessionToken === '' || !hash_equals($sessionToken, $token)) {
            abort(419, 'CSRF token mismatch');
        }

        return $next($request);
    }
}
