<?php

use Codemonster\Cms\Modules\Auth\Controllers\AuthController;
use Codemonster\Cms\Modules\Auth\Middleware\AuthMiddleware;
use Codemonster\Cms\Modules\Auth\Middleware\GuestMiddleware;
use Codemonster\Cms\Modules\Setup\Middleware\RequireInstalled;
use Codemonster\Security\RateLimiting\ThrottleRequests;

router()->get('/login', [AuthController::class, 'showLogin'])
    ->middleware(RequireInstalled::class)
    ->middleware(GuestMiddleware::class);
router()->post('/login', [AuthController::class, 'login'])
    ->middleware(RequireInstalled::class)
    ->middleware(GuestMiddleware::class)
    ->middleware(ThrottleRequests::class, '5,60');

router()->get('/register', [AuthController::class, 'showRegister'])
    ->middleware(RequireInstalled::class)
    ->middleware(GuestMiddleware::class);
router()->post('/register', [AuthController::class, 'register'])
    ->middleware(RequireInstalled::class)
    ->middleware(GuestMiddleware::class)
    ->middleware(ThrottleRequests::class, '5,60');

router()->get('/profile', [AuthController::class, 'profile'])
    ->middleware(RequireInstalled::class)
    ->middleware(AuthMiddleware::class);
router()->post('/logout', [AuthController::class, 'logout'])
    ->middleware(RequireInstalled::class)
    ->middleware(AuthMiddleware::class);
