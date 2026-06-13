<?php

namespace Codemonster\Xen\Modules\Auth\Middleware;

use Codemonster\Http\Request;
use Codemonster\Http\Response;
use Codemonster\Xen\Modules\Auth\Models\User;

class GuestMiddleware
{
    private const USER_CHECK_TTL_SECONDS = 300;

    public function handle(Request $request, callable $next): Response
    {
        $session = session();
        $user = $session->get('user');

        if ($user && !$this->validateUserSession($session, $user)) {
            $user = null;
        }

        if ($user) {
            return Response::redirect('/profile');
        }

        return $next($request);
    }

    private function validateUserSession($session, $user): bool
    {
        $lastCheck = (int) $session->get('user_checked_at', 0);
        $shouldCheck = ($lastCheck + self::USER_CHECK_TTL_SECONDS) <= time();

        if (!$shouldCheck) {
            return true;
        }

        $userId = $user['id'] ?? null;
        $dbUser = null;

        if (is_scalar($userId)) {
            $dbUser = User::query()
                ->where((new User())->getKeyName(), $userId)
                ->first();
        }

        if (!$dbUser) {
            $session->forget('user');
            $session->forget('user_checked_at');

            return false;
        }

        $session->put('user_checked_at', time());

        return true;
    }
}
