> [!IMPORTANT]
> This repository is read-only.
>
> Development happens in the Annabel monorepo:
> https://github.com/codemonster-ru/annabel
>
> Issues and pull requests should be opened there.

# Annabel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/codemonster-ru/annabel.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/annabel)
[![Total Downloads](https://img.shields.io/packagist/dt/codemonster-ru/annabel.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/annabel)
[![License](https://img.shields.io/packagist/l/codemonster-ru/annabel.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/annabel)
[![Tests](https://github.com/codemonster-ru/annabel/actions/workflows/tests.yml/badge.svg)](https://github.com/codemonster-ru/annabel/actions/workflows/tests.yml)

Elegant and lightweight PHP framework for modern web applications.

## Installation

```bash
composer require codemonster-ru/annabel
```

## Quick Start

```php
// public/index.php
require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->run();

// bootstrap/app.php
use Codemonster\Annabel\Application;

$baseDir = __DIR__ . '/..';

$app = new Application($baseDir);

require "$baseDir/routes/web.php";

return $app;

// routes/web.php
router()->get('/', fn() => view('home', ['title' => 'Welcome to Annabel']));
```

## CLI

Annabel ships with a lightweight CLI similar to Laravel's `artisan`. It already supports:

-   `about` - show version, base path, and loaded providers
-   `route:list` - list registered routes
-   `config:get key` - read a config value
-   `container:list` - show container bindings/instances
-   `vendor:publish` - publish package config, migrations, views, or assets
-   `serve` - run PHP built-in server (default 127.0.0.1:8000)
-   With `codemonster-ru/database` installed: `make:migration`, `migrate`, `migrate:rollback`, `migrate:status`, `make:seed`, `seed` (appear in `annabel list`; connection is checked when commands run)

```bash
php vendor/bin/annabel
php vendor/bin/annabel help
php vendor/bin/annabel help list
```

Commands may be registered by service providers and are resolved through the
application container, including constructor dependency injection:

```php
class PackageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->commands([
            SyncPackageCommand::class,
        ]);
    }
}
```

New commands may implement `execute(InputInterface $input, OutputInterface
$output): int`. `ArgvInput` provides positional arguments and parsed long
options; commands return `ExitCode` constants. The legacy `handle(array)` method
remains supported for existing commands.

## Database Integration

Annabel ships with first-class integration for  
[`codemonster-ru/database`](https://github.com/codemonster-ru/database).

### 1. Create `config/database.php`

```php
return [
    'default' => 'mysql',

    'connections' => [
        'mysql' => [
            'driver'   => 'mysql',
            'host'     => '127.0.0.1',
            'port'     => 3306,
            'database' => env('DB_NAME'),
            'username' => env('DB_USER'),
            'password' => env('DB_PASS'),
            'charset'  => 'utf8mb4',
        ],

        'sqlite' => [
            'driver'   => 'sqlite',
            'database' => base_path('database/database.sqlite'),
        ],
    ],
];
```

### 2. Usage

```php
// Query builder
$users = db()->table('users')->where('active', 1)->get();

// Schema builder
schema()->create('posts', function ($table) {
    $table->id();
    $table->string('title');
});

// Transactions
transaction(function () {
    db()->table('logs')->insert(['type' => 'created']);
});
```

## Helpers

| Function                | Description                        |
| ----------------------- | ---------------------------------- |
| `app()`                 | Access the application container   |
| `base_path()`           | Resolve base project paths         |
| `config()`              | Get or set configuration values    |
| `env()`                 | Read environment variables         |
| `dump()` / `dd()`       | Debugging utilities                |
| `request()`             | Get current HTTP request           |
| `response()` / `json()` | Create HTTP response               |
| `router()` / `route()`  | Access router instance             |
| `view()`                | Render or return view instance     |
| `session()`             | Access session store               |
| `old()`                 | Read flashed old form input        |
| `errors()`              | Read flashed validation errors     |
| `cache()`               | Access PSR-16 cache store          |
| `validator()`           | Validate input data                |
| `db()`                  | Get the active database connection |
| `schema()`              | Get the schema builder             |
| `transaction()`         | Execute a DB transaction           |

All helpers are autoloaded automatically.

## Middleware

Annabel supports PSR-15 middleware via `Psr\Http\Server\MiddlewareInterface`.
Route middleware may be registered by class name, and global middleware may be
added to the kernel with `addMiddleware()`.

## Logging

Annabel binds `Psr\Log\LoggerInterface` in the container. Configure the default
channel in `config/logging.php`; unhandled HTTP exceptions are reported before
the error response is rendered.

## Cache

Annabel binds `Psr\SimpleCache\CacheInterface` in the container. Configure the
default store in `config/cache.php`; the framework ships with `array` and `file`
stores.

## Events

Annabel binds `Psr\EventDispatcher\EventDispatcherInterface` and
`Psr\EventDispatcher\ListenerProviderInterface`. Register listeners through the
framework listener provider and dispatch events through the PSR dispatcher.

## Validation

Annabel ships with a small validation layer for request/config data. It supports
common scalar rules, nested fields through dot notation, `validated()` data, and
`validateOrFail()` for exception-driven flows.

```php
$result = validator([
    'email' => 'hello@example.com',
], [
    'email' => 'required|email',
]);

if ($result->fails()) {
    $errors = $result->errors();
}
```

Controllers can use `Codemonster\Annabel\Http\ValidatesRequests` to validate the
current request. Validation failures return JSON `422` responses for API
requests, or redirect back with flashed `errors` and `_old_input` for web forms.
Redirects are restricted to local same-origin locations. Sensitive fields are
excluded recursively according to `config/validation.php`.

```php
use Codemonster\Annabel\Http\ValidatesRequests;
use Codemonster\Http\Request;

class RegisterController
{
    use ValidatesRequests;

    public function store(Request $request): mixed
    {
        $data = $this->validate($request, [
            'email' => 'required|email',
        ]);

        // ...
    }
}
```

## HTTP Exceptions

Framework HTTP exceptions live under `Codemonster\Annabel\Http\Exceptions`.
They expose stable status and header contracts for bad requests, authentication,
authorization, missing routes, and unsupported methods.

## Container parameters

The Annabel container implements `Psr\Container\ContainerInterface`, so it can be
passed to libraries expecting a PSR-11 container.

You can pass named constructor parameters when resolving classes or closure bindings:

```php
$user = app(User::class, ['name' => 'Annabel']);

app()->bind(User::class, fn($container, array $params) => new User($params['name']));
$user = app(User::class, ['name' => 'Annabel']);

// Same for Application::make()
$user = $app->make(User::class, ['name' => 'Annabel']);
```

Note: for singleton bindings, passing parameters after the instance is resolved throws an exception.

Note: `Application::serve()` will throw if an instance already exists; call `Application::resetInstance()` first.

## Providers

Annabel reads provider settings from `config/app.php` before registering services.

```php
return [
    'providers' => [
        'defaults' => true,
        'disabled' => [],
        'extra' => [],
        'discover' => true,
        'path' => base_path('bootstrap/providers'),
        'packages' => [
            'discover' => true,
            'dont_discover' => [],
            'cache' => true,
            'cache_path' => base_path('bootstrap/cache/packages.php'),
        ],
    ],
];
```

All providers are registered first and booted after registration completes.

Installed packages may declare providers in Composer metadata:

```json
{
    "extra": {
        "annabel": {
            "providers": [
                "Vendor\\Package\\PackageServiceProvider"
            ]
        }
    }
}
```

Only providers owned by that package should be declared. Applications can
disable selected packages through `providers.packages.dont_discover`, use `*`
to disable all package discovery, or set `providers.packages.discover` to
`false`. The generated manifest cache is invalidated when package
`composer.json` metadata changes.

### Publishable Resources

Package providers may register publishable files or directory trees:

```php
class PackageServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/package.php' => base_path('config/package.php'),
            __DIR__ . '/../../resources/views' => base_path('resources/views/vendor/package'),
        ], ['config', 'package']);
    }
}
```

Publishing is explicit and does not overwrite existing files unless requested:

```bash
php vendor/bin/annabel vendor:publish --provider="Vendor\\Package\\PackageServiceProvider"
php vendor/bin/annabel vendor:publish --tag=config
php vendor/bin/annabel vendor:publish --all --force
```

Destinations must remain inside the application base path. Symbolic-link escape
paths are rejected.

## Testing

```bash
composer test
```

## Author

[**Kirill Kolesnikov**](https://github.com/KolesnikovKirill)

## License

[MIT](https://github.com/codemonster-ru/annabel/blob/main/LICENSE)
