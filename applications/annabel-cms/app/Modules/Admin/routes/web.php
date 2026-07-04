<?php

use Codemonster\Cms\Modules\Admin\Controllers\DashboardController;
use Codemonster\Cms\Modules\Admin\Middleware\RequireAdmin;
use Codemonster\Cms\Modules\Setup\Middleware\RequireInstalled;
use Codemonster\Security\RateLimiting\ThrottleRequests;

router()->get('/admin', [DashboardController::class, 'index'])
    ->middleware(RequireInstalled::class);
router()->get('/admin/login', [DashboardController::class, 'showLogin'])
    ->middleware(RequireInstalled::class);
router()->get('/admin/forgot-password', [DashboardController::class, 'forgotPassword'])
    ->middleware(RequireInstalled::class);
router()->get('/admin/reset-password', [DashboardController::class, 'showResetPassword'])
    ->middleware(RequireInstalled::class);
router()->post('/admin/login', [DashboardController::class, 'login'])
    ->middleware(RequireInstalled::class)
    ->middleware(ThrottleRequests::class, '5,60');
router()->post('/admin/forgot-password', [DashboardController::class, 'sendForgotPassword'])
    ->middleware(RequireInstalled::class)
    ->middleware(ThrottleRequests::class, '5,60');
router()->post('/admin/reset-password', [DashboardController::class, 'resetPassword'])
    ->middleware(RequireInstalled::class)
    ->middleware(ThrottleRequests::class, '5,60');
router()->post('/admin/logout', [DashboardController::class, 'logout'])
    ->middleware(RequireInstalled::class)
    ->middleware(RequireAdmin::class);
