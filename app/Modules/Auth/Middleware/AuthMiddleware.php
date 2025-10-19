<?php

namespace Codemonster\Xen\Modules\Auth\Middleware;

use Codemonster\Xen\Modules\Auth\Controllers\LoginController;

class AuthMiddleware
{
    public function handle(): void
    {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        if ($path === '/admin/login') {
            return;
        }

        if (!LoginController::check()) {
            header('Location: /admin/login');

            exit;
        }
    }
}
