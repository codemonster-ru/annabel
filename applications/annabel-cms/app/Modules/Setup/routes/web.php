<?php

use Codemonster\Cms\Modules\Setup\Controllers\SetupController;
use Codemonster\Cms\Modules\Setup\Middleware\RedirectIfInstalled;
use Codemonster\Security\RateLimiting\ThrottleRequests;

router()->get('/setup', [SetupController::class, 'index'])
    ->middleware(RedirectIfInstalled::class);

router()->get('/setup/requirements', [SetupController::class, 'requirements'])
    ->middleware(RedirectIfInstalled::class);

router()->post('/setup/database', [SetupController::class, 'database'])
    ->middleware(RedirectIfInstalled::class);

router()->post('/setup', [SetupController::class, 'install'])
    ->middleware(RedirectIfInstalled::class)
    ->middleware(ThrottleRequests::class, '5,60');
