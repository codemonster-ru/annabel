<?php

use Codemonster\Xen\Modules\Admin\Controllers\DashboardController;
use Codemonster\Xen\Modules\Auth\Middleware\AuthMiddleware;

router()->get('/admin', [DashboardController::class, 'index'])
    ->middleware(AuthMiddleware::class, 'admin');
