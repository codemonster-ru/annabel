<?php

namespace Codemonster\Cms\Modules\Admin\Controllers;

use Codemonster\Cms\Modules\Admin\Services\AdminShellRenderer;
use Codemonster\Cms\Modules\Admin\Services\RememberCookieResponseFactory;
use Codemonster\Cms\Modules\Auth\Contracts\AuthenticatorInterface;
use Codemonster\Cms\Modules\Auth\Contracts\UserSessionInterface;
use Codemonster\Http\Request;
use Codemonster\Http\Response;
use Codemonster\Validation\Validator;

class AdminAuthController
{
    public function __construct(
        private AuthenticatorInterface $auth,
        private UserSessionInterface $users,
        private AdminShellRenderer $renderer,
        private RememberCookieResponseFactory $rememberCookies,
        private Validator $validator,
    ) {
    }

    public function showLogin(): Response
    {
        $user = $this->users->current(true);

        if ($user === null) {
            return $this->renderer->render(false, 'login');
        }

        if (!$user->hasRole('admin')) {
            abort(403);
        }

        return Response::redirect('/admin');
    }

    public function login(Request $request): Response
    {
        $validated = $this->validator->validateOrFail([
            'login' => trim((string) $request->input('login')),
            'password' => (string) $request->input('password'),
        ], [
            'login' => 'required',
            'password' => 'required',
        ]);

        $user = $this->auth->attempt($validated['login'], $validated['password']);

        if (!$user) {
            return $this->json([
                'message' => 'Invalid credentials',
            ], 401);
        }

        if (!$user->hasRole('admin')) {
            return $this->json([
                'message' => 'This account does not have admin access',
            ], 403);
        }

        $remember = in_array((string) $request->input('remember'), ['1', 'true', 'on'], true);
        $rememberCookie = $this->users->login($user, $remember);
        $response = $this->json($this->renderer->payload(true));

        if ($remember && is_string($rememberCookie) && $rememberCookie !== '') {
            return $this->rememberCookies->withRememberToken($response, $rememberCookie);
        }

        $this->users->forgetRememberToken($user->id);

        return $this->rememberCookies->withoutRememberToken($response);
    }

    public function logout(Request $request): Response
    {
        $user = $this->users->current(true);

        if ($user !== null) {
            $this->users->forgetRememberToken($user->id);
        }

        $this->users->logout();

        if ($request->wantsJson()) {
            return $this->rememberCookies->withoutRememberToken(
                $this->json($this->renderer->payload(false)),
            );
        }

        return $this->rememberCookies->withoutRememberToken(Response::redirect('/admin/login'));
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function json(array $payload, int $status = 200): Response
    {
        return new Response(json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        ), $status, [
            'Content-Type' => 'application/json; charset=UTF-8',
        ]);
    }
}
