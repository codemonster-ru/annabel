<?php

use Codemonster\Xen\Modules\Auth\Controllers\AuthController;
use Codemonster\Xen\Modules\Auth\Middleware\AuthMiddleware;
use Codemonster\Xen\Modules\Auth\Middleware\CsrfMiddleware;
use Codemonster\Xen\Modules\Auth\Middleware\GuestMiddleware;
use Codemonster\Security\RateLimiting\ThrottleRequests;

router()->get('/login', [AuthController::class, 'showLogin'])
    ->middleware(GuestMiddleware::class);
router()->post('/login', [AuthController::class, 'login'])
    ->middleware(GuestMiddleware::class)
    ->middleware(ThrottleRequests::class, '5,60')
    ->middleware(CsrfMiddleware::class);

router()->get('/register', [AuthController::class, 'showRegister'])
    ->middleware(GuestMiddleware::class);
router()->post('/register', [AuthController::class, 'register'])
    ->middleware(GuestMiddleware::class)
    ->middleware(ThrottleRequests::class, '5,60')
    ->middleware(CsrfMiddleware::class);

router()->get('/profile', [AuthController::class, 'profile'])
    ->middleware(AuthMiddleware::class);
router()->post('/logout', [AuthController::class, 'logout'])
    ->middleware(AuthMiddleware::class)
    ->middleware(CsrfMiddleware::class);
