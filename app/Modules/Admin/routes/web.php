<?php

use Codemonster\Xen\Modules\Admin\Controllers\DashboardController;
use Codemonster\Xen\Modules\Auth\Middleware\AuthMiddleware;

route('/admin', [DashboardController::class, 'index'])
    ->middleware(AuthMiddleware::class);
