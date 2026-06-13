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
        $name = trim($request->input('name'));

        if (!$email || !$password || !$name) {
            return new Response($this->view->render('auth::register', [
                'error' => 'All fields are required',
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

        try {
            $user = transaction(function () use ($name, $email, $password) {
                $user = User::create([
                    'name' => $name,
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
        $email = trim($request->input('email'));
        $password = trim($request->input('password'));

        if (!$email || !$password) {
            return new Response(
                $this->view->render('auth::login', ['error' => 'Email and password are required']),
                422,
            );
        }

        $user = $this->auth->attempt($email, $password);

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

    public function logout(): Response
    {
        $this->users->logout();

        return Response::redirect('/login');
    }
}
