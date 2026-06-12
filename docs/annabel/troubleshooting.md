---
title: "Troubleshooting"
description: "Common Annabel problems and fixes"
order: 6
---

# Troubleshooting

This page collects common setup and runtime problems.

## Application instance is already initialized

Annabel keeps a static application instance. In tests or scripts that bootstrap
more than one application, reset it first:

```php
Codemonster\Annabel\Application::resetInstance();
```

Feature tests using `InteractsWithApplication` can call `refreshApplication()`.

## Routes do not update in production

Clear the route cache:

```bash
php vendor/bin/annabel route:clear
```

Then rebuild optimized caches:

```bash
php vendor/bin/annabel optimize
```

Routes with closures cannot be cached. Use controller handlers for production
routes.

## Config changes are ignored

Clear the config cache:

```bash
php vendor/bin/annabel config:clear
```

When deploying, rebuild caches after environment and config files are in place.

## Cannot write cache, sessions, logs, or generated files

Make sure the PHP process can write to:

- `storage/`
- `bootstrap/cache/`
- the SQLite database file path, when SQLite is used

Generated runtime files should not be committed.

## Database connection fails

Check `.env` and the selected PDO extension:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=annabel
DB_USERNAME=annabel
DB_PASSWORD=secret
```

For SQLite, create the database file before migrating:

```bash
touch database/database.sqlite
```

## CSRF token mismatch

For HTML forms, include the CSRF field:

```php
<?= csrf_field() ?>
```

For JSON APIs, prefer routes under paths excluded from CSRF verification, such
as `api/*`, or configure `security.csrf.verify_json` intentionally.

## Rate limiting uses the wrong client IP

Configure trusted proxies:

```dotenv
SECURITY_TRUSTED_PROXIES=10.0.0.10,10.0.0.11
```

This lets throttling resolve the real client address behind a proxy.

## Queue jobs never run

Check the queue connection and run a worker:

```bash
php vendor/bin/annabel queue:work
```

For database queues, publish and run queue migrations:

```bash
php vendor/bin/annabel vendor:publish --tag=queue-migrations
php vendor/bin/annabel migrate
```

## Redis-backed features fail

Redis cache, queues, sessions, and throttling require a working Redis client.
When using the PHP Redis extension, ensure the extension is installed in the PHP
runtime and that host, port, password, database, and timeout values are correct.

## Mail does not send

Use the `log` mailer locally:

```dotenv
MAIL_MAILER=log
```

For SMTP, configure the DSN:

```dotenv
MAIL_MAILER=smtp
MAILER_DSN=smtp://user:pass@smtp.example.com:587
```
