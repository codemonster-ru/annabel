<?php

use Codemonster\Cms\Modules\Pages\Controllers\PageController;
use Codemonster\Cms\Support\Installation\Middleware\RequireInstalled;

router()->get('/', [PageController::class, 'index'])
    ->middleware(RequireInstalled::class);

router()->get('/pages/{slug}', [PageController::class, 'show'])
    ->middleware(RequireInstalled::class)
    ->where('slug', '[A-Za-z0-9](?:[A-Za-z0-9-]{0,118}[A-Za-z0-9])?');
