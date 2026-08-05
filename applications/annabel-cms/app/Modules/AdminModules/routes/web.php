<?php

use Codemonster\Cms\Modules\Admin\Middleware\RequirePermission;
use Codemonster\Cms\Modules\AdminModules\Controllers\ModuleListController;
use Codemonster\Cms\Modules\AdminModules\Controllers\SystemUpdateController;
use Codemonster\Cms\Support\Installation\Middleware\RequireInstalled;

router()->get('/admin/settings/modules', [ModuleListController::class, 'index'])
    ->middleware(RequireInstalled::class)
    ->middleware(RequirePermission::class, 'modules.view');

router()->get('/admin/settings/modules/data', [ModuleListController::class, 'data'])
    ->middleware(RequireInstalled::class)
    ->middleware(RequirePermission::class, 'modules.view');

router()->post('/admin/settings/modules/data/{name}/enable', [ModuleListController::class, 'enable'])
    ->where('name', '[A-Za-z][A-Za-z0-9_-]*')
    ->middleware(RequireInstalled::class)
    ->middleware(RequirePermission::class, 'modules.manage');

router()->post('/admin/settings/modules/data/{name}/disable', [ModuleListController::class, 'disable'])
    ->where('name', '[A-Za-z][A-Za-z0-9_-]*')
    ->middleware(RequireInstalled::class)
    ->middleware(RequirePermission::class, 'modules.manage');

router()->post('/admin/settings/modules/data/{name}/install', [ModuleListController::class, 'install'])
    ->where('name', '[A-Za-z][A-Za-z0-9_-]*')
    ->middleware(RequireInstalled::class)
    ->middleware(RequirePermission::class, 'modules.manage');

router()->post('/admin/settings/modules/data/{name}/uninstall', [ModuleListController::class, 'uninstall'])
    ->where('name', '[A-Za-z][A-Za-z0-9_-]*')
    ->middleware(RequireInstalled::class)
    ->middleware(RequirePermission::class, 'modules.manage');

router()->post('/admin/settings/modules/data/{name}/update', [ModuleListController::class, 'update'])
    ->where('name', '[A-Za-z][A-Za-z0-9_-]*')
    ->middleware(RequireInstalled::class)
    ->middleware(RequirePermission::class, 'modules.manage');

router()->get('/admin/settings/system/updates', [SystemUpdateController::class, 'index'])
    ->middleware(RequireInstalled::class)
    ->middleware(RequirePermission::class, 'system_updates.view');

router()->get('/admin/settings/system/updates/data', [SystemUpdateController::class, 'data'])
    ->middleware(RequireInstalled::class)
    ->middleware(RequirePermission::class, 'system_updates.view');
