> [!IMPORTANT]
> This repository is read-only.
>
> Development happens in the Annabel monorepo:
> https://github.com/codemonster-ru/annabel
>
> Issues and pull requests should be opened there.

# Annabel Skeleton

[![Latest Version on Packagist](https://img.shields.io/packagist/v/codemonster-ru/annabel-skeleton.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/annabel-skeleton)
[![Total Downloads](https://img.shields.io/packagist/dt/codemonster-ru/annabel-skeleton.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/annabel-skeleton)
[![License](https://img.shields.io/packagist/l/codemonster-ru/annabel-skeleton.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/annabel-skeleton)

Starter project for the **[Annabel PHP Framework](https://github.com/codemonster-ru/annabel)**.
A clean and modern foundation for building full-stack web applications.

## Quick Start

Create a new project using Composer:

```bash
composer create-project codemonster-ru/annabel-skeleton myapp
cd myapp
cp .env.example .env
composer install
composer start
```

Open [http://localhost:8000](http://localhost:8000) to see your first page rendered by **Annabel**.

## Common Commands

The skeleton exposes the most common framework commands as Composer scripts:

```bash
composer serve
composer migrate
composer optimize
composer optimize:clear
composer queue
composer schedule
```

Use `composer annabel -- <command>` or `php vendor/bin/annabel <command>` for
commands that do not have a dedicated script alias.

## Database Setup

Configure your database in `.env`, then run migrations:

```bash
composer migrate
```

For local SQLite development, create the database file before migrating:

```bash
touch database/database.sqlite
```

## Production

Install optimized dependencies, cache framework metadata, and run migrations:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
composer migrate
composer optimize
```

Make sure `storage/`, `bootstrap/cache/`, and your SQLite database path if used
are writable by the PHP process.

Run queue workers under your process supervisor:

```bash
composer queue
```

Run the scheduler every minute from cron:

```cron
* * * * * cd /path/to/app && php vendor/bin/annabel schedule:run >> /dev/null 2>&1
```

## Frontend Assets

The skeleton ships with Vite for CSS and JavaScript assets.

```bash
npm install
npm run dev
```

Use `npm run build` for production assets. Views can include entries with:

```php
<?= vite('resources/js/app.js') ?>
```

## Security Defaults

The `codemonster-ru/security` package is enabled by default.

-   CSRF and throttling are auto-registered in the kernel.
-   Use `.env` to disable or tune `SECURITY_*` settings.

Default `SECURITY_*` values:

```dotenv
SECURITY_CSRF_ENABLED=true
SECURITY_CSRF_ADD_TO_KERNEL=true
SECURITY_CSRF_VERIFY_JSON=false
SECURITY_CSRF_INPUT_KEY=_token
SECURITY_THROTTLE_ENABLED=true
SECURITY_THROTTLE_ADD_TO_KERNEL=true
SECURITY_THROTTLE_MAX_ATTEMPTS=60
SECURITY_THROTTLE_DECAY_SECONDS=60
```

## Sessions

Sessions use the file driver and `storage/sessions` by default. The directory is
created automatically and must be writable by the PHP process.

Set `SESSION_DRIVER=array` for isolated tests or `SESSION_DRIVER=redis` for
multi-node deployments. Redis connection and cookie options live in
`config/session.php`.

## Validation

Validation failures redirect only to local same-origin locations. Old form input
is flashed without sensitive keys configured in `config/validation.php`.

## Providers

Application providers are configured in `config/app.php`. Keep framework
defaults enabled, use `disabled` for explicit opt-outs, and add application
providers through `extra` or `bootstrap/providers/*.php` discovery. Installed
packages may register their own providers through Composer metadata; use
`providers.packages.dont_discover` for application-level opt-outs.

Package config, migrations, views, and assets can be published with
`php vendor/bin/annabel vendor:publish` using an explicit provider, tag, or
`--all` selector.

Package and application service providers may also register container-resolved
CLI commands through their `commands()` method.

## Logging

Logs use `config/logging.php` and write to `storage/logs/annabel.log` by default.

## Cache

Cache uses `config/cache.php` and writes file cache entries to `storage/cache` by
default.

## Events

Events are dispatched through the PSR-14 dispatcher binding. Use the `event()`
helper to dispatch object events.

## Requirements

-   PHP **8.2** or higher
-   Composer

## Author

[**Kirill Kolesnikov**](https://github.com/KolesnikovKirill)

## License

[MIT](https://github.com/codemonster-ru/annabel-skeleton/blob/main/LICENSE)
