<?php

use Codemonster\Cms\Modules\Core\Controllers\SystemController;
use Codemonster\Cms\Modules\Setup\Middleware\RequireInstalled;

router()->get('/system/info', [SystemController::class, 'info'])
    ->middleware(RequireInstalled::class);
