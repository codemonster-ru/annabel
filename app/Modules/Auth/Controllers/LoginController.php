<?php

namespace Codemonster\Xen\Modules\Auth\Controllers;

use Codemonster\View\View;

class LoginController
{
    protected View $view;

    public function __construct(View $view)
    {
        $this->view = $view;
    }

    public function showForm(): string
    {
        return $this->view->render('auth::login');
    }

    public function handle(): void
    {
        session_start();

        $user = $_POST['username'] ?? '';
        $pass = $_POST['password'] ?? '';

        if ($user === 'admin' && $pass === '1234') {
            $_SESSION['user'] = $user;

            header('Location: /admin');

            exit;
        }

        echo 'Invalid credentials';
    }

    public static function check(): bool
    {
        session_start();

        return isset($_SESSION['user']);
    }
}
