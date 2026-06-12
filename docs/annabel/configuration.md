---
title: "Configuration"
description: "Application configuration and environment values"
order: 3
---

# Configuration

Annabel configuration lives in `config/*.php`. Each file returns an array and
may read environment values through `env()`.

## Environment file

Copy the example environment file when creating an application:

```bash
cp .env.example .env
```

Do not commit `.env`. Keep deploy-specific values in the deployment
environment.

## Configuration files

The skeleton ships with these application config files:

| File | Purpose |
| --- | --- |
| `config/app.php` | Providers, discovery, routes, and autoconfiguration. |
| `config/assets.php` | Vite and asset configuration. |
| `config/auth.php` | Auth providers, credentials, abilities, and policies. |
| `config/cache.php` | Default cache store and store definitions. |
| `config/database.php` | Database connections and migration paths. |
| `config/filesystem.php` | Filesystem disks. |
| `config/http-client.php` | HTTP client defaults. |
| `config/logging.php` | Log channels. |
| `config/mail.php` | Mailers and transports. |
| `config/queue.php` | Queue connections, retries, backoff, and timeout. |
| `config/security.php` | CSRF, throttling, trusted proxies, and presets. |
| `config/session.php` | Session storage, cookies, encryption, and Redis. |
| `config/validation.php` | Sensitive fields excluded from flashed old input. |

## Reading config

Read configuration values by dot-separated key, with an optional default for
missing values.

```php
$name = config('app.name');
$debug = config('app.debug', false);
```

Dot notation reads nested values.

## Writing config at runtime

Use runtime writes for values that should change only in the current process.

```php
config([
    'app.debug' => false,
]);
```

Runtime writes affect the current process only. Prefer config files for stable
application configuration.

## Configuration cache

Build the config cache during deployment:

```bash
php vendor/bin/annabel config:cache
```

Clear it when environment or config files change:

```bash
php vendor/bin/annabel config:clear
```

`php vendor/bin/annabel optimize` builds all production caches.

## Provider discovery

`config/app.php` controls framework defaults, application providers, package
provider discovery, attribute route discovery, and service autoconfiguration.

```php
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
```

Use `disabled` for explicit opt-outs from default providers. Use `extra` or
`bootstrap/providers/*.php` for application providers.

## Inspecting configuration

The CLI can print resolved configuration while protecting sensitive values.

```bash
php vendor/bin/annabel config:get app.providers.defaults
php vendor/bin/annabel config:list
```

`config:list` redacts sensitive values before printing them.
