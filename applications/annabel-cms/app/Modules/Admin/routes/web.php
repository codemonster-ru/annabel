<?php

use Codemonster\Cms\Modules\Admin\Controllers\AdminAuthController;
use Codemonster\Cms\Modules\Admin\Controllers\AdminPasswordResetController;
use Codemonster\Cms\Modules\Admin\Controllers\AdminShellController;
use Codemonster\Cms\Modules\Admin\Middleware\RequireAdmin;
use Codemonster\Cms\Support\Installation\Middleware\RequireInstalled;
use Codemonster\Security\RateLimiting\ThrottleRequests;

router()->get('/admin', [AdminShellController::class, 'index'])
    ->middleware(RequireInstalled::class)
    ->middleware(RequireAdmin::class);
router()->get('/admin/login', [AdminAuthController::class, 'showLogin'])
    ->middleware(RequireInstalled::class);
router()->get('/admin/forgot-password', [AdminPasswordResetController::class, 'forgotPassword'])
    ->middleware(RequireInstalled::class);
router()->get('/admin/reset-password', [AdminPasswordResetController::class, 'showResetPassword'])
    ->middleware(RequireInstalled::class);
router()->post('/admin/login', [AdminAuthController::class, 'login'])
    ->middleware(RequireInstalled::class)
    ->middleware(ThrottleRequests::class, '5,60');
router()->post('/admin/forgot-password', [AdminPasswordResetController::class, 'sendForgotPassword'])
    ->middleware(RequireInstalled::class)
    ->middleware(ThrottleRequests::class, '5,60');
router()->post('/admin/reset-password', [AdminPasswordResetController::class, 'resetPassword'])
    ->middleware(RequireInstalled::class)
    ->middleware(ThrottleRequests::class, '5,60');
router()->post('/admin/logout', [AdminAuthController::class, 'logout'])
    ->middleware(RequireInstalled::class)
    ->middleware(RequireAdmin::class);
