---
title: "Getting started"
description: "Overview and first Annabel application"
order: 1
---

# Getting started

Annabel is a small PHP framework for building web applications with explicit
configuration, service providers, a container, routing, middleware, views,
database access, queues, scheduled tasks, and testing helpers.

The framework is assembled from independent Codemonster packages, but
application documentation is organized around the tasks you perform in an
Annabel app: routing, configuration, validation, sessions, queues, migrations,
and deployment.

## Create your first application

```bash
composer create-project codemonster-ru/annabel-skeleton myapp
cd myapp
cp .env.example .env
composer install
composer serve
```

Open `http://localhost:8000`.

## Directory overview

- `app/`: application controllers and models.
- `bootstrap/app.php`: application bootstrap.
- `bootstrap/providers/`: application service providers.
- `config/`: framework and package configuration.
- `database/migrations/`: application migrations.
- `public/index.php`: front controller.
- `resources/`: views, CSS, and JavaScript.
- `routes/web.php`: HTTP routes.
- `routes/schedule.php`: scheduled tasks.

## First route

```php
use App\Controllers\HomeController;

$app->get('/', [HomeController::class, 'index']);
```

## First controller

```php
namespace App\Controllers;

final class HomeController
{
    public function index(): mixed
    {
        return view('home', [
            'title' => 'Welcome to Annabel',
        ]);
    }
}
```

## Next steps

Start with these pages:

- [Installation](installation.md)
- [Configuration](configuration.md)
- [Directory structure](directory-structure.md)
- [Troubleshooting](troubleshooting.md)
- [Request lifecycle](architecture/request-lifecycle.md)
- [Routing](basics/routing.md)
- [Controllers](basics/controllers.md)
- [Views](basics/views.md)
- [Database getting started](database/getting-started.md)
- [Testing getting started](testing/getting-started.md)

Then try a recipe:

- [Build a CRUD resource](recipes/crud.md)
- [Build a login flow](recipes/login.md)
- [Build a JSON API endpoint](recipes/json-api.md)
