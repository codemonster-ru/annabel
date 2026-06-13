<?php

use Codemonster\Cms\Modules\Pages\Controllers\PageController;

router()->get('/', [PageController::class, 'index']);
