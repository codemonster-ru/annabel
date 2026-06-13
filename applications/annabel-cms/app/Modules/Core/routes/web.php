<?php

use Codemonster\Cms\Modules\Core\Controllers\SystemController;

router()->get('/system/info', [SystemController::class, 'info']);
