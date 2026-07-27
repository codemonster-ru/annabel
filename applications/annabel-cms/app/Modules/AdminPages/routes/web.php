<?php

use Codemonster\Cms\Modules\Admin\Middleware\RequireAdmin;
use Codemonster\Cms\Modules\AdminPages\Controllers\PageManagementController;
use Codemonster\Cms\Support\Installation\Middleware\RequireInstalled;

router()->get('/admin/pages', [PageManagementController::class, 'index'])
    ->middleware(RequireInstalled::class)
    ->middleware(RequireAdmin::class);

router()->get('/admin/pages/create', [PageManagementController::class, 'create'])
    ->middleware(RequireInstalled::class)
    ->middleware(RequireAdmin::class);

router()->get('/admin/pages/{id}/edit', [PageManagementController::class, 'edit'])
    ->middleware(RequireInstalled::class)
    ->middleware(RequireAdmin::class)
    ->where('id', '\\d+');

router()->get('/admin/pages/data', [PageManagementController::class, 'data'])
    ->middleware(RequireInstalled::class)
    ->middleware(RequireAdmin::class);

router()->get('/admin/pages/data/{id}', [PageManagementController::class, 'showData'])
    ->middleware(RequireInstalled::class)
    ->middleware(RequireAdmin::class)
    ->where('id', '\\d+');

router()->post('/admin/pages/preferences', [PageManagementController::class, 'updatePreferences'])
    ->middleware(RequireInstalled::class)
    ->middleware(RequireAdmin::class);

router()->post('/admin/pages/data', [PageManagementController::class, 'store'])
    ->middleware(RequireInstalled::class)
    ->middleware(RequireAdmin::class);

router()->post('/admin/pages/data/{id}', [PageManagementController::class, 'update'])
    ->middleware(RequireInstalled::class)
    ->middleware(RequireAdmin::class)
    ->where('id', '\\d+');

router()->post('/admin/pages/data/{id}/delete', [PageManagementController::class, 'destroy'])
    ->middleware(RequireInstalled::class)
    ->middleware(RequireAdmin::class)
    ->where('id', '\\d+');
