<?php

use Codemonster\Cms\Modules\Admin\Controllers\DashboardController;
use Codemonster\Cms\Modules\Admin\Middleware\RequireAdmin;
use Codemonster\Security\RateLimiting\ThrottleRequests;

router()->get('/admin', [DashboardController::class, 'index']);
router()->post('/admin/login', [DashboardController::class, 'login'])
    ->middleware(ThrottleRequests::class, '5,60');
router()->post('/admin/logout', [DashboardController::class, 'logout'])
    ->middleware(RequireAdmin::class);
