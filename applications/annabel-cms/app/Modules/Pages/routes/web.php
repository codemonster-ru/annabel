<?php

use Codemonster\Cms\Modules\Pages\Controllers\PageController;
use Codemonster\Cms\Modules\Setup\Middleware\RequireInstalled;

router()->get('/', [PageController::class, 'index'])
    ->middleware(RequireInstalled::class);
