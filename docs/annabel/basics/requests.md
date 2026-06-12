---
title: "Requests"
description: "Reading request data in controllers"
order: 4
---

# Requests

Controllers may type-hint `Codemonster\Http\Request`.

```php
use Codemonster\Http\Request;

public function store(Request $request): mixed
{
    $email = $request->input('email');

    // ...
}
```

## Current request helper

```php
$request = request();
```

## Route parameters

Route parameters are injected by name:

```php
router()->get('/users/{id}', [UserController::class, 'show']);

public function show(string $id): mixed
{
    // ...
}
```

Type-hint the request alongside route parameters when needed.
