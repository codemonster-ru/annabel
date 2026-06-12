---
title: "Getting started"
description: "First standalone usage of codemonster-ru/router"
order: 1
---

# Getting started

`codemonster-ru/router` registers routes, route groups, middleware metadata,
route names, constraints, and matches requests by method and URI.

## Basic usage

The following example creates a router, registers a route, and dispatches a
request.

```php
use Codemonster\Router\Router;

$router = new Router();

$router->get('/users/{id}', [UserController::class, 'show'])
    ->where('id', '\d+')
    ->name('users.show');

$route = $router->dispatch('GET', '/users/15');
```

The router returns a matched route. Standalone applications are responsible for
calling the handler and producing a response.
