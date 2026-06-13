<?php

use Codemonster\Xen\Modules\Core\Controllers\SystemController;

route('/system/info', [SystemController::class, 'info']);
