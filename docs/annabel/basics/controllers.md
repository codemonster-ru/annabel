---
title: "Controllers"
description: "Controller handlers and dependency injection"
order: 3
---

# Controllers

Controllers keep route files compact and make route caching possible.

## Create a controller

Generate controllers in the conventional application directory.

```bash
php vendor/bin/annabel make:controller PostController
```

## Handler

Controller methods receive resolved dependencies and return
response-compatible values.

```php
namespace App\Controllers;

use Codemonster\Http\Request;

final class PostController
{
    public function store(Request $request): mixed
    {
        $title = $request->input('title');

        // ...

        return response()->redirect('/posts');
    }
}
```

## Route

Register the controller method as the handler for an HTTP route.

```php
router()->post('/posts', [PostController::class, 'store']);
```

Controllers are resolved through the container, so constructor dependencies may
be type-hinted.

## Route parameters and requests

Type-hint the request alongside named route parameters when the action needs
both.

```php
use Codemonster\Http\Request;

final class UserController
{
    public function show(Request $request, string $id): mixed
    {
        return json([
            'id' => $id,
            'query' => $request->query('tab'),
        ]);
    }
}
```

Route parameters are matched by parameter name. The current request may be
type-hinted alongside route parameters.

## Generated controllers

`make:controller Admin/UserController` creates nested controller directories
under `app/Controllers`.
