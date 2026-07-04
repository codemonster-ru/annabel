<?php

namespace Codemonster\Cms\Modules\Admin\Controllers;

use Codemonster\Cms\Modules\Admin\Services\AdminAssetManager;
use Codemonster\Cms\Modules\Auth\Contracts\AuthenticatorInterface;
use Codemonster\Cms\Modules\Auth\Contracts\UserSessionInterface;
use Codemonster\Cms\Modules\Auth\Models\User;
use Codemonster\Cms\Modules\Core\ModuleManager;
use Codemonster\Http\Request;
use Codemonster\Http\Response;
use Codemonster\Mail\Contracts\MailerInterface;
use Codemonster\Mail\Message;
use Codemonster\Validation\Validator;
use Codemonster\View\View;

class DashboardController
{
    private const PASSWORD_RESET_TTL_SECONDS = 3600;

    public function __construct(
        private ModuleManager $manager,
        private AuthenticatorInterface $auth,
        private UserSessionInterface $users,
        private MailerInterface $mailer,
        private AdminAssetManager $assets,
        private View $view,
    ) {
    }

    public function index(): Response
    {
        $user = $this->users->current(true);

        if ($user === null) {
            return Response::redirect('/admin/login');
        }

        if (!$this->users->hasRole('admin')) {
            abort(403);
        }

        return $this->renderAdmin(true, 'dashboard');
    }

    public function showLogin(): Response
    {
        $user = $this->users->current(true);

        if ($user === null) {
            return $this->renderAdmin(false, 'login');
        }

        if (!$this->users->hasRole('admin')) {
            abort(403);
        }

        return Response::redirect('/admin');
    }

    public function forgotPassword(): Response
    {
        $user = $this->users->current(true);
        $isAuthenticated = $user !== null && $this->users->hasRole('admin');

        if ($user !== null && !$isAuthenticated) {
            abort(403);
        }

        if ($isAuthenticated) {
            return Response::redirect('/admin');
        }

        return $this->renderAdmin(false, 'forgot-password');
    }

    public function showResetPassword(Request $request): Response
    {
        $user = $this->users->current(true);
        $isAuthenticated = $user !== null && $this->users->hasRole('admin');

        if ($user !== null && !$isAuthenticated) {
            abort(403);
        }

        if ($isAuthenticated) {
            return Response::redirect('/admin');
        }

        $token = trim((string) $request->input('token'));

        if ($token === '' || $this->validPasswordResetRecord($token) === null) {
            return Response::redirect('/admin/forgot-password');
        }

        return $this->renderAdmin(false, 'reset-password', [
            'resetToken' => $token,
        ]);
    }

    public function login(Request $request): Response
    {
        $validated = $this->validate([
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

        $response = $this->json($this->adminPayload(true));

        if ($remember && is_string($rememberCookie) && $rememberCookie !== '') {
            return $this->withRememberCookie($response, $rememberCookie);
        }

        $this->users->forgetRememberToken($user->id);

        return $this->withoutRememberCookie($response);
    }

    public function sendForgotPassword(Request $request): Response
    {
        $validated = $this->validate([
            'email' => trim((string) $request->input('email')),
        ], [
            'email' => 'required|email',
        ]);

        $user = $this->findAdminUserByEmail($validated['email']);

        if ($user !== null) {
            $token = $this->issuePasswordResetToken($user);

            try {
                $this->sendPasswordResetEmail($user, $token, $request);
            } catch (\Throwable $e) {
                $this->deletePasswordResetTokensForUser((int) $user->id);

                throw $e;
            }
        }

        return $this->json([
            'message' => 'If an admin account with that email exists, we have sent a password reset link.',
        ]);
    }

    public function resetPassword(Request $request): Response
    {
        $validated = $this->validate([
            'token' => trim((string) $request->input('token')),
            'password' => (string) $request->input('password'),
            'password_confirmation' => (string) $request->input('password_confirmation'),
        ], [
            'token' => 'required',
            'password' => 'required|min:8|confirmed',
            'password_confirmation' => 'required',
        ]);

        $record = $this->validPasswordResetRecord($validated['token']);

        if ($record === null) {
            return $this->json([
                'message' => 'The password reset link is invalid or has expired.',
                'errors' => [
                    'token' => ['The password reset link is invalid or has expired.'],
                ],
            ], 422);
        }

        $user = User::find($record['user_id']);

        if (!$user instanceof User || !$user->hasRole('admin')) {
            $this->deletePasswordResetTokensForUser((int) ($record['user_id'] ?? 0));

            return $this->json([
                'message' => 'The password reset link is invalid or has expired.',
                'errors' => [
                    'token' => ['The password reset link is invalid or has expired.'],
                ],
            ], 422);
        }

        transaction(function () use ($user, $validated): void {
            $user->password = password_hash($validated['password'], PASSWORD_BCRYPT);
            $user->remember_token = null;
            $user->save();

            $this->deletePasswordResetTokensForUser((int) $user->id);
        });

        return $this->withoutRememberCookie($this->json([
            'message' => 'Password updated successfully.',
            'redirect' => '/admin/login',
        ]));
    }

    public function logout(Request $request): Response
    {
        $user = $this->users->current(true);

        if ($user !== null) {
            $this->users->forgetRememberToken($user->id);
        }

        $this->users->logout();

        if ($request->wantsJson()) {
            return $this->withoutRememberCookie($this->json($this->adminPayload(false)));
        }

        return $this->withoutRememberCookie(Response::redirect('/admin/login'));
    }

    /**
     * @return array{
     *     authenticated: bool,
     *     screen: string,
     *     csrfToken: string,
     *     user: array{id: int|string, email: string, roles: array<int, string>, role: string}|null,
     *     modules: array<string, string>,
     *     resetToken: string|null
     * }
     * @param array<string, mixed> $extra
     */
    private function adminPayload(bool $isAuthenticated, string $screen = 'login', array $extra = []): array
    {
        return array_merge([
            'authenticated' => $isAuthenticated,
            'screen' => $isAuthenticated ? 'dashboard' : $screen,
            'csrfToken' => csrf_token(),
            'user' => $isAuthenticated ? $this->users->current()?->toArray() : null,
            'modules' => $isAuthenticated ? $this->manager->listAll() : [],
            'resetToken' => null,
        ], $extra);
    }

    /**
     * @param array<string, mixed> $extra
     */
    private function renderAdmin(bool $isAuthenticated, string $screen, array $extra = []): Response
    {
        return new Response($this->view->render('admin::app', [
            'boot' => $this->adminPayload($isAuthenticated, $screen, $extra),
            'assets' => $this->assets->entrypoints(),
        ]));
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

    /**
     * @param array<string, mixed> $data
     * @param array<string, string|list<string>> $rules
     * @return array<string, mixed>
     */
    private function validate(array $data, array $rules): array
    {
        /** @var Validator $validator */
        $validator = app(Validator::class);

        return $validator->validateOrFail($data, $rules);
    }

    private function findAdminUserByEmail(string $email): ?User
    {
        $user = User::findByEmail($email);

        if (!$user instanceof User || !$user->hasRole('admin')) {
            return null;
        }

        return $user;
    }

    private function issuePasswordResetToken(User $user): string
    {
        $token = bin2hex(random_bytes(32));
        $now = date('Y-m-d H:i:s');
        $expiresAt = date('Y-m-d H:i:s', time() + self::PASSWORD_RESET_TTL_SECONDS);

        transaction(function () use ($user, $token, $now, $expiresAt): void {
            $this->deletePasswordResetTokensForUser((int) $user->id);

            db()->table('password_reset_tokens')->insert([
                'user_id' => $user->id,
                'token_hash' => hash('sha256', $token),
                'expires_at' => $expiresAt,
                'created_at' => $now,
            ]);
        });

        return $token;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function validPasswordResetRecord(string $token): ?array
    {
        $this->deleteExpiredPasswordResetTokens();

        $record = db()->table('password_reset_tokens')
            ->where('token_hash', hash('sha256', $token))
            ->first();

        if (!is_array($record)) {
            return null;
        }

        $expiresAt = $record['expires_at'] ?? null;

        if (!is_string($expiresAt) || strtotime($expiresAt) === false || strtotime($expiresAt) < time()) {
            return null;
        }

        return $record;
    }

    private function sendPasswordResetEmail(User $user, string $token, Request $request): void
    {
        $fromAddress = (string) config('mail.from.address', 'hello@example.com');
        $fromName = (string) config('mail.from.name', 'Annabel');
        $url = sprintf(
            '%s://%s/admin/reset-password?token=%s',
            $request->scheme(),
            $request->host(),
            rawurlencode($token),
        );

        $this->mailer->send(
            Message::make()
                ->from($fromAddress, $fromName)
                ->to((string) $user->email)
                ->subject('Reset your Annabel CMS password')
                ->text(
                    "We received a request to reset your Annabel CMS admin password.\n\n"
                    . "Open this link to choose a new password:\n{$url}\n\n"
                    . 'This link will expire in 60 minutes.'
                ),
        );
    }

    private function deletePasswordResetTokensForUser(int $userId): void
    {
        if ($userId <= 0) {
            return;
        }

        db()->table('password_reset_tokens')
            ->where('user_id', $userId)
            ->delete();
    }

    private function deleteExpiredPasswordResetTokens(): void
    {
        db()->table('password_reset_tokens')
            ->where('expires_at', '<=', date('Y-m-d H:i:s'))
            ->delete();
    }

    private function withRememberCookie(Response $response, string $value): Response
    {
        return $response->withCookie(
            $this->users->rememberCookieName(),
            $value,
            $this->rememberCookieOptions(time() + $this->users->rememberCookieLifetime()),
        );
    }

    private function withoutRememberCookie(Response $response): Response
    {
        return $response->withCookie(
            $this->users->rememberCookieName(),
            '',
            $this->rememberCookieOptions(time() - 3600, 0),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function rememberCookieOptions(int $expiresAt, ?int $maxAge = null): array
    {
        $sessionCookie = (array) config('session.cookie', []);
        $options = [
            'expires' => $expiresAt,
            'path' => is_string($sessionCookie['path'] ?? null) ? $sessionCookie['path'] : '/',
            'secure' => (bool) ($sessionCookie['secure'] ?? false),
            'httponly' => (bool) ($sessionCookie['httponly'] ?? true),
            'samesite' => is_string($sessionCookie['samesite'] ?? null) ? $sessionCookie['samesite'] : 'Lax',
        ];

        if ($maxAge !== null) {
            $options['max_age'] = $maxAge;
        }

        return $options;
    }
}
