<?php

use Codemonster\Xen\Modules\Auth\Controllers\LoginController;

route('/admin/login', [LoginController::class, 'showForm']);
route('/admin/login', [LoginController::class, 'handle'], 'POST');
