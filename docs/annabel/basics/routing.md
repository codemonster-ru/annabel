---
title: "Routing"
description: "Defining routes, route parameters, names, and groups"
order: 1
---

# Routing

Define application routes in `routes/web.php`.

## Basic routes

Register routes by HTTP method and provide a callback or controller handler.

```php
router()->get('/', [HomeController::class, 'index']);
router()->post('/posts', [PostController::class, 'store']);
router()->any('/webhook', [WebhookController::class, 'handle']);
```

Use `router()->add()` when registering several methods explicitly:

```php
router()->add(
    ['PUT', 'PATCH'],
    '/profile',
    [ProfileController::class, 'update'],
);
```

## Parameters

Declare path parameters in braces and receive their values in the route handler.

```php
router()->get('/users/{id}', [UserController::class, 'show'])
    ->where('id', '\\d+');
```

Route parameters are injected into closures and controller methods by name.

```php
router()->get('/users/{id}', function (string $id) {
    return json(['id' => $id]);
});
```

## Named routes

Name routes that must be referenced when generating URLs or redirects.

```php
router()->get('/users/{id}', [UserController::class, 'show'])
    ->name('users.show');

$url = route('users.show', ['id' => 42]);
```

## Groups

Groups apply a URI prefix and may inherit middleware from parent groups:

```php
router()->group('/admin', function () {
    router()->get('/users', [AdminUserController::class, 'index'])
        ->name('admin.users.index');
});
```

## Listing routes

Inspect registered routes to verify their methods, paths, names, and middleware.

```bash
php vendor/bin/annabel route:list
```

## Route cache

Use controller handlers for routes that should be cached. Closure routes cannot
be cached.

```bash
php vendor/bin/annabel route:cache
php vendor/bin/annabel route:clear
```

Attribute routes can be discovered from `app/Controllers` when enabled in
`config/app.php`.
