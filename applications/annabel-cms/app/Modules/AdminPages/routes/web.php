<?php

use Codemonster\Cms\Modules\Admin\Middleware\RequirePermission;
use Codemonster\Cms\Modules\AdminPages\Controllers\PageManagementController;
use Codemonster\Cms\Support\Installation\Middleware\RequireInstalled;

router()->get('/admin/pages', [PageManagementController::class, 'index'])
    ->middleware(RequireInstalled::class)
    ->middleware(RequirePermission::class, 'pages.view');

router()->get('/admin/pages/create', [PageManagementController::class, 'create'])
    ->middleware(RequireInstalled::class)
    ->middleware(RequirePermission::class, 'pages.create');

router()->get('/admin/pages/{id}/edit', [PageManagementController::class, 'edit'])
    ->middleware(RequireInstalled::class)
    ->middleware(RequirePermission::class, 'pages.update,pages.update.own')
    ->where('id', '\\d+');

router()->get('/admin/pages/data', [PageManagementController::class, 'data'])
    ->middleware(RequireInstalled::class)
    ->middleware(RequirePermission::class, 'pages.view');

router()->get('/admin/pages/data/{id}', [PageManagementController::class, 'showData'])
    ->middleware(RequireInstalled::class)
    ->middleware(RequirePermission::class, 'pages.update,pages.update.own')
    ->where('id', '\\d+');

router()->post('/admin/pages/preferences', [PageManagementController::class, 'updatePreferences'])
    ->middleware(RequireInstalled::class)
    ->middleware(RequirePermission::class, 'pages.view');

router()->post('/admin/pages/data', [PageManagementController::class, 'store'])
    ->middleware(RequireInstalled::class)
    ->middleware(RequirePermission::class, 'pages.create');

router()->post('/admin/pages/data/{id}', [PageManagementController::class, 'update'])
    ->middleware(RequireInstalled::class)
    ->middleware(RequirePermission::class, 'pages.update,pages.update.own')
    ->where('id', '\\d+');

router()->post('/admin/pages/data/{id}/delete', [PageManagementController::class, 'destroy'])
    ->middleware(RequireInstalled::class)
    ->middleware(RequirePermission::class, 'pages.delete,pages.delete.own')
    ->where('id', '\\d+');
