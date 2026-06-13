<?php

namespace Codemonster\Cms\Modules\Admin\Controllers;

use Codemonster\Cms\Modules\Admin\Services\AdminAssetManager;
use Codemonster\Cms\Modules\Auth\Contracts\AuthenticatorInterface;
use Codemonster\Cms\Modules\Auth\Contracts\UserSessionInterface;
use Codemonster\Cms\Modules\Core\ModuleManager;
use Codemonster\Http\Request;
use Codemonster\Http\Response;
use Codemonster\View\View;

class DashboardController
{
    public function __construct(
        private ModuleManager $manager,
        private AuthenticatorInterface $auth,
        private UserSessionInterface $users,
        private AdminAssetManager $assets,
        private View $view,
    ) {
    }

    public function index(): Response
    {
        $user = $this->users->current(true);
        $isAuthenticated = $user !== null && $this->users->hasRole('admin');

        if ($user !== null && !$isAuthenticated) {
            abort(403);
        }

        return new Response($this->view->render('admin::app', [
            'boot' => $this->adminPayload($isAuthenticated),
            'assets' => $this->assets->entrypoints(),
        ]));
    }

    public function login(Request $request): Response
    {
        $email = trim((string) $request->input('email'));
        $password = trim((string) $request->input('password'));

        if (!$email || !$password) {
            return $this->json([
                'message' => 'Email and password are required',
            ], 422);
        }

        $user = $this->auth->attempt($email, $password);

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

        $this->users->login($user);

        return $this->json($this->adminPayload(true));
    }

    public function logout(Request $request): Response
    {
        $this->users->logout();

        if ($request->wantsJson()) {
            return $this->json($this->adminPayload(false));
        }

        return Response::redirect('/admin');
    }

    /**
     * @return array{
     *     authenticated: bool,
     *     csrfToken: string,
     *     user: array{id: int|string, email: string, roles: array<int, string>, role: string}|null,
     *     modules: array<string, string>
     * }
     */
    private function adminPayload(bool $isAuthenticated): array
    {
        return [
            'authenticated' => $isAuthenticated,
            'csrfToken' => csrf_token(),
            'user' => $isAuthenticated ? $this->users->current()?->toArray() : null,
            'modules' => $isAuthenticated ? $this->manager->listAll() : [],
        ];
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
