<?php

use Codemonster\Cms\Modules\Admin\Middleware\RequireAdmin;
use Codemonster\Cms\Modules\AdminUsers\Controllers\UserListController;
use Codemonster\Cms\Support\Installation\Middleware\RequireInstalled;

router()->get('/admin/users', [UserListController::class, 'index'])
    ->middleware(RequireInstalled::class)
    ->middleware(RequireAdmin::class);

router()->get('/admin/users/data', [UserListController::class, 'data'])
    ->middleware(RequireInstalled::class)
    ->middleware(RequireAdmin::class);
