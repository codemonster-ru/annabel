<?php

use Codemonster\Cms\Modules\Admin\Middleware\RequirePermission;
use Codemonster\Cms\Modules\AdminSettings\Controllers\SiteSettingsController;
use Codemonster\Cms\Support\Installation\Middleware\RequireInstalled;

router()->get('/admin/settings/general', [SiteSettingsController::class, 'index'])
    ->middleware(RequireInstalled::class)
    ->middleware(RequirePermission::class, 'settings.view');

router()->get('/admin/settings/general/data', [SiteSettingsController::class, 'data'])
    ->middleware(RequireInstalled::class)
    ->middleware(RequirePermission::class, 'settings.view');

router()->post('/admin/settings/general/data', [SiteSettingsController::class, 'update'])
    ->middleware(RequireInstalled::class)
    ->middleware(RequirePermission::class, 'settings.update');
