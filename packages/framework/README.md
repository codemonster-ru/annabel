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
-   `config:list` - list config values with secrets redacted
-   `container:list` - show container bindings/instances
-   `vendor:publish` - publish package config, migrations, views, or assets
-   `serve` - run PHP built-in server (default 127.0.0.1:8000)
-   `make:controller`, `make:model`, `make:middleware`, `make:request`, `make:policy` - generate application classes
-   With `codemonster-ru/database` installed: `make:migration`, `migrate`, `migrate:rollback`, `migrate:status`, `make:seed`, `seed` (appear in `annabel list`; connection is checked when commands run)

```bash
php vendor/bin/annabel
php vendor/bin/annabel help
php vendor/bin/annabel help list
php vendor/bin/annabel make:controller Admin/User
php vendor/bin/annabel make:model User
php vendor/bin/annabel make:policy Post
php vendor/bin/annabel queue:work --once
php vendor/bin/annabel schedule:run
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

## Testing

Application tests can use Annabel's lightweight HTTP helpers:

```php
use Codemonster\Annabel\Application;
use Codemonster\Annabel\Testing\InteractsWithApplication;
use PHPUnit\Framework\TestCase;

class FeatureTest extends TestCase
{
    use InteractsWithApplication;

    protected function createApplication(): Application
    {
        return require __DIR__ . '/../bootstrap/app.php';
    }

    public function test_homepage(): void
    {
        $this->get('/')->assertOk()->assertSee('Welcome');
    }
}
```

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
| `http_client()`         | Access the HTTP client             |
| `router()`              | Access router or register route    |
| `route()`               | Generate a named route URI         |
| `view()`                | Render or return view instance     |
| `session()`             | Access session store               |
| `storage()`             | Access filesystem storage disks    |
| `old()`                 | Read flashed old form input        |
| `errors()`              | Read flashed validation errors     |
| `auth()`                | Access the authentication guard    |
| `user()`                | Read the authenticated user        |
| `cache()`               | Access PSR-16 cache store          |
| `mailer()`              | Access mailers                     |
| `queue()`               | Access queue connections           |
| `dispatch()`            | Dispatch a queue job               |
| `schedule()`            | Access scheduled tasks             |
| `validator()`           | Validate input data                |
| `db()`                  | Get the active database connection |
| `schema()`              | Get the schema builder             |
| `transaction()`         | Execute a DB transaction           |

All helpers are autoloaded automatically.

## Filesystem

Annabel registers `codemonster-ru/filesystem` by default. Publish the default
config and use `storage()` to read or write files:

```bash
php vendor/bin/annabel vendor:publish --tag=filesystem
```

```php
storage('public')->put('avatars/user-1.txt', 'avatar');

$url = storage('public')->url('avatars/user-1.txt');
```

## HTTP Client

Annabel registers `codemonster-ru/http-client` by default. Configure defaults in
`config/http-client.php` and use `http_client()` for external API calls:

```php
$response = http_client()
    ->baseUrl('https://api.example.com')
    ->acceptJson()
    ->get('/users/1');

$user = $response->throw()->json();
```

## Middleware

Annabel supports PSR-15 middleware via `Psr\Http\Server\MiddlewareInterface`.
Route middleware may be registered by class name, and global middleware may be
added to the kernel with `addMiddleware()`.

Middleware aliases and groups keep routes compact:

```php
router()->get('/dashboard', [DashboardController::class, 'index'])
    ->middleware('auth');

router()->get('/posts/{post}', [PostController::class, 'show'])
    ->middleware('can:posts.view,post');

router()->post('/posts', [PostController::class, 'store'])
    ->middleware('web');
```

The framework registers `auth` and `can` when auth is enabled. The security
provider registers `csrf`, `throttle`, and the default `web` / `api` groups.
Custom aliases and groups can be registered on the HTTP kernel:

```php
app(\Codemonster\Annabel\Http\Kernel::class)
    ->aliasMiddleware('admin', App\Http\Middleware\AdminOnly::class);
```

Publish the security config to tune CSRF, rate-limit storage, trusted proxies,
and named throttle presets:

```bash
php vendor/bin/annabel vendor:publish --tag=security
```

```php
router()->post('/login', [LoginController::class, 'store'])
    ->middleware('throttle:login');
```

## Authentication

Annabel registers `codemonster-ru/auth` by default. Publish the default config
and configure a user provider in `config/auth.php`, or provide a small in-memory
list for local applications:

```bash
php vendor/bin/annabel vendor:publish --tag=auth
```

```php
return [
    'provider' => 'database',
    'database' => [
        'table' => 'users',
        'identifier_column' => 'id',
        'password_column' => 'password',
    ],
    'users' => [
        new App\User(1, 'admin@example.com', password_hash('secret', PASSWORD_DEFAULT)),
    ],
    'redirect_to' => '/login',
];
```

```php
if (auth()->attempt(['email' => $email, 'password' => $password])) {
    return response()->redirect('/dashboard');
}

router()->get('/dashboard', [DashboardController::class, 'index'])
    ->middleware('auth');
```

Production applications should bind a database-backed
`Codemonster\Auth\Contracts\UserProviderInterface` implementation through
`auth.provider`.

## Routing

Routes support dynamic parameters, constraints, names, and URI generation:

```php
router()->get('/users/{id}', [UserController::class, 'show'])
    ->where('id', '\d+')
    ->name('users.show');

route('users.show', ['id' => 42]); // /users/42
```

Route parameters are injected into closures and controllers by parameter name.
The current `Codemonster\Http\Request` may be type-hinted alongside route
parameters.

## API Resources

API resources provide one transformation for individual models, collections,
and existing `simplePaginate()` results:

```php
use Codemonster\ApiResource\JsonResource;

final class UserResource extends JsonResource
{
    public function toArray(): array
    {
        return [
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
        ];
    }
}

return UserResource::paginated(
    User::query()->simplePaginate(20, $page),
    '/api/users',
)->response();
```

## Logging

Annabel binds `Psr\Log\LoggerInterface` in the container. Configure the default
channel in `config/logging.php`; unhandled HTTP exceptions are reported before
the error response is rendered.

## Cache

Annabel binds `Psr\SimpleCache\CacheInterface` in the container. Configure the
default store in `config/cache.php`; the framework ships with `array`, `file`,
and `redis` stores. Set `CACHE_STORE=redis` and configure `REDIS_HOST`,
`REDIS_PORT`, `REDIS_PASSWORD`, and `REDIS_CACHE_DB` for shared cache in
multi-instance deployments.

## Mail

Annabel registers `codemonster-ru/mail` by default. Configure the default
mailer in `config/mail.php`; the framework ships with `array`, `log`,
`sendmail`, and Symfony-powered `smtp` transports. Set `MAIL_MAILER=smtp` and
provide an SMTP DSN through `MAILER_DSN`.

```php
use Codemonster\Mail\Message;

mailer('log')->send(
    Message::make()
        ->from('hello@example.com', 'Annabel')
        ->to('user@example.com')
        ->subject('Welcome')
        ->text('Welcome to Annabel.'),
);
```

## Queue

Annabel registers `codemonster-ru/queue` by default. Configure the default
connection in `config/queue.php`; the framework ships with `sync`, `database`,
and `redis` drivers.

```php
use Codemonster\Queue\Contracts\JobInterface;

class SendWelcomeEmailJob implements JobInterface
{
    public function handle(): void
    {
        //
    }
}

dispatch(new SendWelcomeEmailJob());
```

The default `sync` connection runs jobs immediately. For SQL-backed background
jobs, set `QUEUE_CONNECTION=database`, publish queue migrations, run `migrate`,
and start the worker:

```bash
php vendor/bin/annabel vendor:publish --tag=queue-migrations
php vendor/bin/annabel migrate
php vendor/bin/annabel queue:work
php vendor/bin/annabel queue:work --stop-when-empty
php vendor/bin/annabel queue:failed
php vendor/bin/annabel queue:retry 1
php vendor/bin/annabel queue:retry all
php vendor/bin/annabel queue:flush
```

For Redis-backed workers, set `QUEUE_CONNECTION=redis` and configure
`REDIS_HOST`, `REDIS_PORT`, `REDIS_PASSWORD`, `REDIS_QUEUE_DB`, and
`QUEUE_REDIS_PREFIX`. Redis failed jobs are stored in Redis and work with the
same `queue:failed`, `queue:retry`, and `queue:flush` commands.

## Scheduler

Annabel registers `codemonster-ru/scheduler` by default. Define tasks in
`routes/schedule.php` and run `schedule:run` every minute from cron:

```php
use Codemonster\Scheduler\Schedule;

/** @var Schedule $schedule */
$schedule->call(fn () => cleanup(), 'cleanup')
    ->dailyAt('03:00')
    ->withoutOverlapping();
```

```bash
* * * * * php /path/to/app/vendor/bin/annabel schedule:run
```

Use `schedule:list` to inspect registered tasks, cron expressions, due status,
and overlap locks.

Scheduler locks use the configured cache store when the cache provider is
registered.

## Production optimization

Build configuration and route caches during deployment:

```bash
php vendor/bin/annabel optimize
```

Routes with closures cannot be cached. Use controller handlers such as
`[HomeController::class, 'index']`. Clear all generated caches before changing
environment configuration or when troubleshooting:

```bash
php vendor/bin/annabel optimize:clear
```

The individual `config:cache`, `config:clear`, `route:cache`, and `route:clear`
commands are also available.

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
