<?php

use Codemonster\Cms\Modules\Admin\Middleware\RequireAdmin;
use Codemonster\Cms\Modules\AdminUsers\Controllers\RoleListController;
use Codemonster\Cms\Modules\AdminUsers\Controllers\UserListController;
use Codemonster\Cms\Support\Installation\Middleware\RequireInstalled;

router()->get('/admin/users', [UserListController::class, 'index'])
    ->middleware(RequireInstalled::class)
    ->middleware(RequireAdmin::class);

router()->get('/admin/users/create', [UserListController::class, 'create'])
    ->middleware(RequireInstalled::class)
    ->middleware(RequireAdmin::class);

router()->get('/admin/users/{id}/edit', [UserListController::class, 'edit'])
    ->where('id', '\\d+')
    ->middleware(RequireInstalled::class)
    ->middleware(RequireAdmin::class);

router()->get('/admin/users/data', [UserListController::class, 'data'])
    ->middleware(RequireInstalled::class)
    ->middleware(RequireAdmin::class);

router()->get('/admin/users/data/{id}', [UserListController::class, 'showData'])
    ->where('id', '\\d+')
    ->middleware(RequireInstalled::class)
    ->middleware(RequireAdmin::class);

router()->post('/admin/users/preferences', [UserListController::class, 'updatePreferences'])
    ->middleware(RequireInstalled::class)
    ->middleware(RequireAdmin::class);

router()->post('/admin/users/data', [UserListController::class, 'store'])
    ->middleware(RequireInstalled::class)
    ->middleware(RequireAdmin::class);

router()->post('/admin/users/data/{id}', [UserListController::class, 'update'])
    ->where('id', '\\d+')
    ->middleware(RequireInstalled::class)
    ->middleware(RequireAdmin::class);

router()->post('/admin/users/data/{id}/delete', [UserListController::class, 'destroy'])
    ->where('id', '\\d+')
    ->middleware(RequireInstalled::class)
    ->middleware(RequireAdmin::class);

router()->get('/admin/groups', [RoleListController::class, 'index'])->middleware(RequireInstalled::class)->middleware(RequireAdmin::class);
router()->get('/admin/groups/create', [RoleListController::class, 'create'])->middleware(RequireInstalled::class)->middleware(RequireAdmin::class);
router()->get('/admin/groups/{id}/edit', [RoleListController::class, 'edit'])->where('id', '\\d+')->middleware(RequireInstalled::class)->middleware(RequireAdmin::class);
router()->get('/admin/groups/data', [RoleListController::class, 'data'])->middleware(RequireInstalled::class)->middleware(RequireAdmin::class);
router()->get('/admin/groups/data/{id}', [RoleListController::class, 'showData'])->where('id', '\\d+')->middleware(RequireInstalled::class)->middleware(RequireAdmin::class);
router()->post('/admin/groups/preferences', [RoleListController::class, 'updatePreferences'])->middleware(RequireInstalled::class)->middleware(RequireAdmin::class);
router()->post('/admin/groups/data', [RoleListController::class, 'store'])->middleware(RequireInstalled::class)->middleware(RequireAdmin::class);
router()->post('/admin/groups/data/{id}', [RoleListController::class, 'update'])->where('id', '\\d+')->middleware(RequireInstalled::class)->middleware(RequireAdmin::class);
router()->post('/admin/groups/data/{id}/delete', [RoleListController::class, 'destroy'])->where('id', '\\d+')->middleware(RequireInstalled::class)->middleware(RequireAdmin::class);
