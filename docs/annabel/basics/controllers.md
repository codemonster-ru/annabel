---
title: "Controllers"
description: "Controller handlers and dependency injection"
order: 3
---

# Controllers

Controllers keep route files compact and make route caching possible.

## Create a controller

```bash
php vendor/bin/annabel make:controller PostController
```

## Handler

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

```php
router()->post('/posts', [PostController::class, 'store']);
```

Controllers are resolved through the container, so constructor dependencies may
be type-hinted.

## Route parameters and requests

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
