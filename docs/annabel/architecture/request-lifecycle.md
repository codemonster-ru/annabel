---
title: "Request lifecycle"
description: "How Annabel handles an HTTP request"
order: 1
---

# Request lifecycle

Every HTTP request enters the application through `public/index.php`.

## Front controller

The front controller boots the application and sends the response produced for
the incoming request.

```php
require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->run();
```

## Bootstrap

`bootstrap/app.php` creates the application, loads environment and
configuration, registers providers, and loads routes.

## Providers

Service providers register container bindings first. After registration,
providers are booted so integrations can safely use services registered by
other providers.

## HTTP kernel

The HTTP kernel receives the current request, runs global middleware, dispatches
the matched route, runs route middleware, and returns a response.

## Response

Handlers may return response objects, views, arrays, strings, or values the
framework can convert into an HTTP response.
