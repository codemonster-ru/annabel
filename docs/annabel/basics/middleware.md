---
title: "Middleware"
description: "Global middleware, route middleware, aliases, and groups"
order: 2
---

# Middleware

Annabel supports PSR-15 middleware.

## Route middleware

Attach middleware to a route when processing applies only to that endpoint.

```php
router()->get('/dashboard', [DashboardController::class, 'index'])
    ->middleware('auth');
```

Middleware may also receive arguments:

```php
router()->post('/login', [LoginController::class, 'store'])
    ->middleware('throttle:login');
```

## Aliases and groups

Framework providers register common aliases such as `auth`, `can`, `csrf`, and
`throttle` when the related services are enabled.

Custom aliases may be registered on the HTTP kernel:

```php
app(Codemonster\Annabel\Http\Kernel::class)
    ->aliasMiddleware('admin', App\Http\Middleware\AdminOnly::class);
```

## Global middleware

Global middleware runs for every request and belongs in application bootstrap or
provider configuration.
