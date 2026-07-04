<?php

namespace Codemonster\Cms\Modules\Auth\Controllers;

use Codemonster\Cms\Modules\Auth\Contracts\AuthenticatedUser;
use Codemonster\Cms\Modules\Auth\Contracts\AuthenticatorInterface;
use Codemonster\Cms\Modules\Auth\Contracts\UserSessionInterface;
use Codemonster\Cms\Modules\Auth\Models\User;
use Codemonster\Http\Request;
use Codemonster\Http\Response;
use Codemonster\View\View;

class AuthController
{
    public function __construct(
        private AuthenticatorInterface $auth,
        private UserSessionInterface $users,
        private View $view,
    ) {
    }

    public function showLogin(): Response
    {
        return new Response($this->view->render('auth::login'));
    }

    public function showRegister(): Response
    {
        return new Response($this->view->render('auth::register'));
    }

    public function register(Request $request): Response
    {
        $email = trim($request->input('email'));
        $password = trim($request->input('password'));
        $passwordConfirmation = trim($request->input('password_confirmation'));
        $username = trim($request->input('username'));

        if (!$email || !$password || !$username) {
            return new Response($this->view->render('auth::register', [
                'error' => 'All fields are required',
            ]), 422);
        }

        if (!User::validUsername($username)) {
            return new Response($this->view->render('auth::register', [
                'error' => 'Username must be 3-60 characters and contain only letters, numbers, underscores, or hyphens',
            ]), 422);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new Response($this->view->render('auth::register', [
                'error' => 'Invalid email address',
            ]), 422);
        }

        if (strlen($password) < 8) {
            return new Response($this->view->render('auth::register', [
                'error' => 'Password must be at least 8 characters',
            ]), 422);
        }

        if ($password !== $passwordConfirmation) {
            return new Response($this->view->render('auth::register', [
                'error' => 'Passwords do not match',
            ]), 422);
        }

        if (User::findByEmail($email)) {
            return new Response($this->view->render('auth::register', [
                'error' => 'Email already in use',
            ]), 409);
        }

        if (User::findByUsername($username)) {
            return new Response($this->view->render('auth::register', [
                'error' => 'Username already in use',
            ]), 409);
        }

        try {
            $user = transaction(function () use ($username, $email, $password) {
                $user = User::create([
                    'username' => $username,
                    'email' => $email,
                    'password' => password_hash($password, PASSWORD_BCRYPT),
                ]);

                $user->assignRole('user');

                return $user;
            });
        } catch (\Throwable $e) {
            if (env('APP_DEBUG', false, true)) {
                throw $e;
            }

            return new Response($this->view->render('auth::register', [
                'error' => 'Registration failed. Please try again.',
            ]), 500);
        }

        $this->users->login(new AuthenticatedUser(
            $user->id,
            (string) $user->email,
            $user->roleNames(),
        ));

        $intended = session()->get('intended_url');

        session()->forget('intended_url');

        return Response::redirect(is_string($intended) && $intended !== '' ? $intended : '/');
    }

    public function login(Request $request): Response
    {
        $login = trim($request->input('login'));
        $password = trim($request->input('password'));

        if (!$login || !$password) {
            return new Response(
                $this->view->render('auth::login', ['error' => 'Login and password are required']),
                422,
            );
        }

        $user = $this->auth->attempt($login, $password);

        if (!$user) {
            return new Response(
                $this->view->render('auth::login', ['error' => 'Invalid credentials']),
                401,
            );
        }

        $this->users->login($user);

        $intended = session()->get('intended_url');

        session()->forget('intended_url');

        return Response::redirect(is_string($intended) && $intended !== '' ? $intended : '/');
    }

    public function profile(): Response
    {
        $user = $this->users->current();

        return new Response($this->view->render('auth::profile', [
            'user' => $user?->toArray(),
        ]));
    }

    public function logout(Request $request): Response
    {
        $user = $this->users->current(true);

        if ($user !== null) {
            $this->users->forgetRememberToken($user->id);
        }

        $this->users->logout();

        return Response::redirect('/login')->withCookie(
            $this->users->rememberCookieName(),
            '',
            [
                'expires' => time() - 3600,
                'max_age' => 0,
                'path' => '/',
                'secure' => (bool) config('session.cookie.secure', false),
                'httponly' => (bool) config('session.cookie.httponly', true),
                'samesite' => (string) config('session.cookie.samesite', 'Lax'),
            ],
        );
    }
}
