<?php

namespace Codemonster\Cms\Modules\Admin\Controllers;

use Codemonster\Cms\Modules\Admin\Services\AdminPasswordResetMailer;
use Codemonster\Cms\Modules\Admin\Services\AdminShellRenderer;
use Codemonster\Cms\Modules\Admin\Services\RememberCookieResponseFactory;
use Codemonster\Cms\Modules\Auth\Contracts\UserSessionInterface;
use Codemonster\Cms\Modules\Auth\Models\User;
use Codemonster\Cms\Modules\Auth\Services\PasswordResetTokenService;
use Codemonster\Http\Request;
use Codemonster\Http\Response;
use Codemonster\Validation\Validator;

class AdminPasswordResetController
{
    private const PASSWORD_RESET_TTL_SECONDS = 3600;

    public function __construct(
        private UserSessionInterface $users,
        private AdminShellRenderer $renderer,
        private PasswordResetTokenService $tokens,
        private AdminPasswordResetMailer $mailer,
        private RememberCookieResponseFactory $rememberCookies,
        private Validator $validator,
    ) {
    }

    public function forgotPassword(): Response
    {
        $redirect = $this->redirectIfAuthenticatedAdmin();

        if ($redirect !== null) {
            return $redirect;
        }

        return $this->renderer->render(false, 'forgot-password');
    }

    public function showResetPassword(Request $request): Response
    {
        $redirect = $this->redirectIfAuthenticatedAdmin();

        if ($redirect !== null) {
            return $redirect;
        }

        $token = trim((string) $request->input('token'));

        if ($token === '' || $this->tokens->validRecord($token) === null) {
            return Response::redirect('/admin/forgot-password');
        }

        return $this->renderer->render(false, 'reset-password', [
            'resetToken' => $token,
        ]);
    }

    public function sendForgotPassword(Request $request): Response
    {
        $validated = $this->validator->validateOrFail([
            'email' => trim((string) $request->input('email')),
        ], [
            'email' => 'required|email',
        ]);

        $user = $this->findAdminUserByEmail($validated['email']);

        if ($user !== null) {
            $token = $this->tokens->issue($user, self::PASSWORD_RESET_TTL_SECONDS);

            try {
                $this->mailer->send($user, $token, $request);
            } catch (\Throwable $e) {
                $this->tokens->deleteForUser((int) $user->id);

                throw $e;
            }
        }

        return $this->json([
            'message' => 'If an admin account with that email exists, we have sent a password reset link.',
        ]);
    }

    public function resetPassword(Request $request): Response
    {
        $validated = $this->validator->validateOrFail([
            'token' => trim((string) $request->input('token')),
            'password' => (string) $request->input('password'),
            'password_confirmation' => (string) $request->input('password_confirmation'),
        ], [
            'token' => 'required',
            'password' => 'required|min:8|confirmed',
            'password_confirmation' => 'required',
        ]);

        $record = $this->tokens->validRecord($validated['token']);

        if ($record === null) {
            return $this->invalidTokenResponse();
        }

        $user = User::find($record['user_id']);

        if (!$user instanceof User || !$user->hasRole('admin')) {
            $this->tokens->deleteForUser((int) ($record['user_id'] ?? 0));

            return $this->invalidTokenResponse();
        }

        transaction(function () use ($user, $validated): void {
            $user->password = password_hash($validated['password'], PASSWORD_DEFAULT);
            $user->remember_token = null;
            $user->save();

            $this->tokens->deleteForUser((int) $user->id);
        });

        return $this->rememberCookies->withoutRememberToken($this->json([
            'message' => 'Password updated successfully.',
            'redirect' => '/admin/login',
        ]));
    }

    private function redirectIfAuthenticatedAdmin(): ?Response
    {
        $user = $this->users->current(true);

        if ($user === null) {
            return null;
        }

        if (!$user->hasRole('admin')) {
            abort(403);
        }

        return Response::redirect('/admin');
    }

    private function findAdminUserByEmail(string $email): ?User
    {
        $user = User::findByEmail($email);

        if (!$user instanceof User || !$user->hasRole('admin')) {
            return null;
        }

        return $user;
    }

    private function invalidTokenResponse(): Response
    {
        return $this->json([
            'message' => 'The password reset link is invalid or has expired.',
            'errors' => [
                'token' => ['The password reset link is invalid or has expired.'],
            ],
        ], 422);
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
