<?php

use App\Controllers\DemoController;
use App\Controllers\HomeController;

$app->get('/', [HomeController::class, 'index']);
$app->get('/demo', [DemoController::class, 'index']);
$app->post('/demo/cache', [DemoController::class, 'cache']);
$app->post('/demo/session', [DemoController::class, 'session']);
$app->post('/demo/validation', [DemoController::class, 'validation']);
$app->post('/demo/database', [DemoController::class, 'database']);
$app->post('/demo/filesystem', [DemoController::class, 'filesystem']);
$app->post('/demo/events', [DemoController::class, 'events']);
$app->post('/demo/queue', [DemoController::class, 'queue']);
$app->post('/demo/mail', [DemoController::class, 'mail']);
$app->post('/demo/auth', [DemoController::class, 'auth']);
$app->post('/demo/config', [DemoController::class, 'config']);
$app->post('/demo/http-client', [DemoController::class, 'httpClient']);
$app->post('/demo/errors', [DemoController::class, 'errors']);
$app->get('/demo/json', [DemoController::class, 'json']);
