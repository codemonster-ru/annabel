<?php

use Codemonster\Router\Router;
use Codemonster\Xen\Modules\Pages\Controllers\PageController;

$router = app(Router::class);

$router->get('/', [PageController::class, 'index']);
