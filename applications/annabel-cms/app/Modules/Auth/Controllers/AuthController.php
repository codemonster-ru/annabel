<?php

namespace Codemonster\Xen\Modules\Auth\Controllers;

use Codemonster\Http\Request;
use Codemonster\Http\Response;
use Codemonster\Xen\Modules\Auth\Models\User;

class AuthController
{
    public function showLogin(): Response
    {
        return new Response(view('auth::login'));
    }

    public function showRegister(): Response
    {
        return new Response(view('auth::register'));
    }

    public function register(Request $request): Response
    {
        $email = trim($request->input('email'));
        $password = trim($request->input('password'));
        $passwordConfirmation = trim($request->input('password_confirmation'));
        $name = trim($request->input('name'));

        if (!$email || !$password || !$name) {
            return new Response(view('auth::register', [
                'error' => 'All fields are required'
            ]), 422);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new Response(view('auth::register', [
                'error' => 'Invalid email address'
            ]), 422);
        }

        if (strlen($password) < 8) {
            return new Response(view('auth::register', [
                'error' => 'Password must be at least 8 characters'
            ]), 422);
        }

        if ($password !== $passwordConfirmation) {
            return new Response(view('auth::register', [
                'error' => 'Passwords do not match'
            ]), 422);
        }

        if (User::findByEmail($email)) {
            return new Response(view('auth::register', [
                'error' => 'Email already in use'
            ]), 409);
        }

        try {
            [$user, $roles] = transaction(function () use ($name, $email, $password) {
                $user = User::create([
                    'name'     => $name,
                    'email'    => $email,
                    'password' => password_hash($password, PASSWORD_BCRYPT),
                ]);

                $user->assignRole('user');

                return [$user, $user->roleNames()];
            });
        } catch (\Throwable $e) {
            if (env('APP_DEBUG', false, true)) {
                throw $e;
            }

            return new Response(view('auth::register', [
                'error' => 'Registration failed. Please try again.'
            ]), 500);
        }

        session()->put('user', [
            'id'    => $user->id,
            'email' => $user->email,
            'roles' => $roles,
            'role'  => $roles[0] ?? 'user'
        ]);

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
                view('auth::login', ['error' => 'Email and password are required']),
                422
            );
        }

        $user = User::findByEmail($email);

        if (!$user || !password_verify($password, (string)$user->password)) {
            return new Response(
                view('auth::login', ['error' => 'Invalid credentials']),
                401
            );
        }

        $roles = $user->roleNames();

        session()->put('user', [
            'id'    => $user->id,
            'email' => $user->email,
            'roles' => $roles,
            'role'  => $roles[0] ?? 'user',
        ]);

        $intended = session()->get('intended_url');

        session()->forget('intended_url');

        return Response::redirect(is_string($intended) && $intended !== '' ? $intended : '/');
    }

    public function profile(): Response
    {
        $user = session()->get('user');

        return new Response(view('auth::profile', [
            'user' => $user,
        ]));
    }

    public function logout(): Response
    {
        session()->forget('user');

        return Response::redirect('/login');
    }
}
