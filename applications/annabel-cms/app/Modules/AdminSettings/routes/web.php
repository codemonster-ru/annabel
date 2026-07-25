<?php

use Codemonster\Cms\Modules\Admin\Middleware\RequireAdmin;
use Codemonster\Cms\Modules\AdminSettings\Controllers\SiteSettingsController;
use Codemonster\Cms\Support\Installation\Middleware\RequireInstalled;

router()->get('/admin/settings/general', [SiteSettingsController::class, 'index'])
    ->middleware(RequireInstalled::class)
    ->middleware(RequireAdmin::class);

router()->get('/admin/settings/general/data', [SiteSettingsController::class, 'data'])
    ->middleware(RequireInstalled::class)
    ->middleware(RequireAdmin::class);

router()->post('/admin/settings/general/data', [SiteSettingsController::class, 'update'])
    ->middleware(RequireInstalled::class)
    ->middleware(RequireAdmin::class);
