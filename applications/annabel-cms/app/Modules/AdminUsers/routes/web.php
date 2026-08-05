<?php

use Codemonster\Cms\Modules\Admin\Middleware\RequirePermission;
use Codemonster\Cms\Modules\AdminUsers\Controllers\RoleListController;
use Codemonster\Cms\Modules\AdminUsers\Controllers\UserListController;
use Codemonster\Cms\Support\Installation\Middleware\RequireInstalled;

router()->get('/admin/users', [UserListController::class, 'index'])
    ->middleware(RequireInstalled::class)
    ->middleware(RequirePermission::class, 'users.view');

router()->get('/admin/users/create', [UserListController::class, 'create'])
    ->middleware(RequireInstalled::class)
    ->middleware(RequirePermission::class, 'users.create');

router()->get('/admin/users/{id}/edit', [UserListController::class, 'edit'])
    ->where('id', '\\d+')
    ->middleware(RequireInstalled::class)
    ->middleware(RequirePermission::class, 'users.update');

router()->get('/admin/users/data', [UserListController::class, 'data'])
    ->middleware(RequireInstalled::class)
    ->middleware(RequirePermission::class, 'users.view');

router()->get('/admin/users/data/{id}', [UserListController::class, 'showData'])
    ->where('id', '\\d+')
    ->middleware(RequireInstalled::class)
    ->middleware(RequirePermission::class, 'users.update');

router()->get('/admin/users/role-options', [UserListController::class, 'roleOptions'])
    ->middleware(RequireInstalled::class)
    ->middleware(RequirePermission::class, 'users.create');

router()->post('/admin/users/preferences', [UserListController::class, 'updatePreferences'])
    ->middleware(RequireInstalled::class)
    ->middleware(RequirePermission::class, 'users.view');

router()->post('/admin/users/data', [UserListController::class, 'store'])
    ->middleware(RequireInstalled::class)
    ->middleware(RequirePermission::class, 'users.create');

router()->post('/admin/users/data/{id}', [UserListController::class, 'update'])
    ->where('id', '\\d+')
    ->middleware(RequireInstalled::class)
    ->middleware(RequirePermission::class, 'users.update');

router()->post('/admin/users/data/{id}/delete', [UserListController::class, 'destroy'])
    ->where('id', '\\d+')
    ->middleware(RequireInstalled::class)
    ->middleware(RequirePermission::class, 'users.delete');

router()->get('/admin/roles', [RoleListController::class, 'index'])
    ->middleware(RequireInstalled::class)
    ->middleware(RequirePermission::class, 'roles.view');

router()->get('/admin/roles/create', [RoleListController::class, 'create'])
    ->middleware(RequireInstalled::class)
    ->middleware(RequirePermission::class, 'roles.create');

router()->get('/admin/roles/{id}/edit', [RoleListController::class, 'edit'])
    ->where('id', '\\d+')
    ->middleware(RequireInstalled::class)
    ->middleware(RequirePermission::class, 'roles.update');

router()->get('/admin/roles/data', [RoleListController::class, 'data'])
    ->middleware(RequireInstalled::class)
    ->middleware(RequirePermission::class, 'roles.view');

router()->get('/admin/roles/data/{id}', [RoleListController::class, 'showData'])
    ->where('id', '\\d+')
    ->middleware(RequireInstalled::class)
    ->middleware(RequirePermission::class, 'roles.update');

router()->get('/admin/roles/permission-options', [RoleListController::class, 'permissionOptions'])
    ->middleware(RequireInstalled::class)
    ->middleware(RequirePermission::class, 'roles.create');

router()->post('/admin/roles/preferences', [RoleListController::class, 'updatePreferences'])
    ->middleware(RequireInstalled::class)
    ->middleware(RequirePermission::class, 'roles.view');

router()->post('/admin/roles/data', [RoleListController::class, 'store'])
    ->middleware(RequireInstalled::class)
    ->middleware(RequirePermission::class, 'roles.create');

router()->post('/admin/roles/data/{id}', [RoleListController::class, 'update'])
    ->where('id', '\\d+')
    ->middleware(RequireInstalled::class)
    ->middleware(RequirePermission::class, 'roles.update');

router()->post('/admin/roles/data/{id}/delete', [RoleListController::class, 'destroy'])
    ->where('id', '\\d+')
    ->middleware(RequireInstalled::class)
    ->middleware(RequirePermission::class, 'roles.delete');
