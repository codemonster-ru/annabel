# codemonster-ru/security

[![Latest Version on Packagist](https://img.shields.io/packagist/v/codemonster-ru/security.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/security)
[![Total Downloads](https://img.shields.io/packagist/dt/codemonster-ru/security.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/security)
[![License](https://img.shields.io/packagist/l/codemonster-ru/security.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/security)
[![Tests](https://github.com/codemonster-ru/security/actions/workflows/tests.yml/badge.svg)](https://github.com/codemonster-ru/security/actions/workflows/tests.yml)

`codemonster-ru/security` is a set of reusable security components for the Annabel ecosystem:

-   CSRF protection (`VerifyCsrfToken`) with a token from POST (`_token`) and/or headers (`X-CSRF-TOKEN`, `X-XSRF-TOKEN`)
-   Rate limiting / brute-force protection (`ThrottleRequests`) with a configurable key and storage layer

No Laravel/Symfony dependencies. Compatible with `codemonster-ru/http` and `codemonster-ru/session`.

## Installation

```bash
composer require codemonster-ru/security
```

For monorepo development, you can use a `path` repository (as in `annabel-skeleton/composer.local.json`).

## Quick Start (Annabel)

Annabel loads providers from `bootstrap/providers/*.php`.

1. Add a provider:

`bootstrap/providers/SecurityServiceProvider.php`

```php
<?php

namespace App\Providers;

use Codemonster\Security\Providers\SecurityServiceProvider as BaseSecurityServiceProvider;

class SecurityServiceProvider extends BaseSecurityServiceProvider {}
```

2. Add config:

`config/security.php`

```php
<?php

return [
    'csrf' => [
        'enabled' => true,
        'add_to_kernel' => true,
        'verify_json' => false,
        'input_key' => '_token',
        'except_methods' => ['GET', 'HEAD', 'OPTIONS'],
        'except' => ['api/*'],
    ],
    'throttle' => [
        'enabled' => true,
        'add_to_kernel' => false,
        'max_attempts' => 60,
        'decay_seconds' => 60,
        'storage' => 'session', // session | database | redis
        'connection' => null, // database connection name
        'table' => 'throttle_requests',
        'redis' => null, // Redis client instance or container id/class
        'prefix' => 'throttle:',
        'presets' => [
            'login' => [
                'ip' => ['max_attempts' => 60, 'decay_seconds' => 60],
                'account' => [
                    'max_attempts' => 5,
                    'decay_seconds' => 60,
                    'field' => 'email',
                ],
            ],
            'api' => ['max_attempts' => 120, 'decay_seconds' => 60],
        ],
        'except' => [],
        'trusted_proxies' => ['10.0.0.0/8'],
    ],
];
```

By default, CSRF is enabled globally (via `Kernel::addMiddleware`), but throttling is not (so as not to surprise all routes).

## CSRF

### How is it checked?

`Codemonster\Security\Csrf\VerifyCsrfToken`:

-   Skips methods from `except_methods` (`GET/HEAD/OPTIONS` by default)
-   By default, **does not validate JSON requests** (if `Accept: application/json`) to avoid breaking the API
-   Validates the token:
    -   In the body: `_token` (configured via `input_key`)
    -   Or in the headers: `X-CSRF-TOKEN`, `X-XSRF-TOKEN`
-   On error, returns `419` (`application/json` or `text/plain`)

Security note: if your API uses cookies or other stateful auth, enable `verify_json` to protect JSON POST/PUT/PATCH/DELETE requests too.

### Helpers

The package autoloads helpers:

-   `csrf_token(): string`
-   `csrf_field(): string` - ready-to-use `<input type="hidden" name="_token" ...>`

Example in the form:

```php
echo '<form method="POST" action="/submit">';
echo csrf_field();
echo '<button type="submit">OK</button>';
echo '</form>';
```

## Throttle / Rate limiting

`Codemonster\Security\RateLimiting\ThrottleRequests`:

-   stores the attempt counter in storage via `ThrottleStorageInterface`
-   the package contains at least one implementation: `SessionThrottleStorage` (without a database)
-   for shared storages, prefer atomic increments (implement `AtomicThrottleStorageInterface`) to avoid race conditions
-   returns `429` + headers:
    -   `Retry-After` (seconds)
    -   `X-RateLimit-Limit`
    -   `X-RateLimit-Remaining`
    -   `RateLimit-Limit`
    -   `RateLimit-Remaining`
    -   `RateLimit-Reset` (unix timestamp)

### Best practices

-   Enable `verify_json` for stateful APIs (cookies, sessions) to avoid CSRF bypasses.
-   Configure `trusted_proxies` when running behind a proxy; otherwise `X-Forwarded-For` should be ignored.
-   Use database or Redis storage in multi-node deployments to avoid per-node limits.

### Trusted proxies

If your app sits behind a reverse proxy or load balancer, configure `trusted_proxies` so the middleware can safely use `X-Forwarded-For` or `X-Real-IP`.
When `trusted_proxies` is empty, only `REMOTE_ADDR` is trusted. Do not trust these headers unless the proxy is under your control.

### Database storage (MySQL)

If you want atomic throttling across multiple nodes, use the database storage:

```php
'throttle' => [
    'storage' => 'database',
    'connection' => null,
    'table' => 'throttle_requests',
],
```

Register the package migrations path (Annabel):

```php
// config/database.php
return [
    'migrations' => [
        'paths' => [
            base_path('database/migrations'),
            base_path('vendor/codemonster-ru/security/migrations'),
        ],
    ],
];
```

Without Annabel/Database:

1. Copy migrations from `vendor/codemonster-ru/security/migrations` into your project migrations directory.
2. Run your migrations as usual.

Custom table name example:

```php
'throttle' => [
    'storage' => 'database',
    'table' => 'app_rate_limits',
],
```

Note: the bundled migration reads `security.throttle.table` to decide which table to create.

If you don't use migrations, create the table manually (adjust name if needed):

```sql
CREATE TABLE `throttle_requests` (
  `key` VARCHAR(191) NOT NULL,
  `attempts` INT NOT NULL DEFAULT 0,
  `expires_at` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Redis storage

Provide a Redis client and set storage to `redis`:

```php
'throttle' => [
    'storage' => 'redis',
    'redis' => Redis::class, // container id/class or instance
    'prefix' => 'throttle:',
],
```

### Connection to the route

Router/Kernel in Annabel supports route-level middleware:

```php
use Codemonster\Security\RateLimiting\ThrottleRequests;

$app->post('/login', fn($req) => 'ok')
    ->middleware(ThrottleRequests::class, '5,60'); // 5 attempts in 60 seconds
```

Preset example:

```php
$app->post('/login', fn($req) => 'ok')
    ->middleware(ThrottleRequests::class, 'login');
```

### Restriction key

By default, the key is built from `ip|method|path` and hashed (`sha1`).

You can pass a callable instead of a role string:

```php
use Codemonster\Security\RateLimiting\ThrottleRequests;

$app->post('/login', fn($req) => 'ok')
    ->middleware(ThrottleRequests::class, function ($req) {
        return 'login:' . ($req->input('email') ?? 'guest') . '|' . $req->ip();
    });
```

## Tests

```bash
composer test
```

Optional E2E env (tests are skipped if not set):

-   MySQL: `MYSQL_HOST`, `MYSQL_PORT`, `MYSQL_DATABASE`, `MYSQL_USERNAME`, `MYSQL_PASSWORD`
-   Redis: `REDIS_HOST`, `REDIS_PORT`, `REDIS_PASSWORD`, `REDIS_DB`

## Author

[**Kirill Kolesnikov**](https://github.com/KolesnikovKirill)

## License

[MIT](https://github.com/codemonster-ru/security/blob/main/LICENSE)
