---
title: "Deployment"
description: "Production deployment checklist"
order: 5
---

# Deployment

Production deployment should install optimized dependencies, configure the
environment, build assets, run migrations, warm caches, and restart workers.

## Install dependencies

Install locked production dependencies and compile frontend assets when the
application uses them.

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci
npm run build
```

Skip Node.js steps when the application does not use Vite assets.

## Configure environment

Set production values in `.env` or your platform environment:

```dotenv
APP_ENV=production
APP_DEBUG=false
```

Configure database, cache, session, queue, mail, and security values for the
target environment.

Recommended production defaults:

```dotenv
APP_ENV=production
APP_DEBUG=false
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
MAIL_MAILER=smtp
SESSION_COOKIE_SECURE=true
```

## Writable paths

The PHP process must be able to write to:

- `storage/`
- `bootstrap/cache/`
- the SQLite database path, when SQLite is used

## Migrate and optimize

Apply pending schema changes before building the production caches.

```bash
php vendor/bin/annabel migrate
php vendor/bin/annabel optimize
```

Clear caches when configuration or routes change:

```bash
php vendor/bin/annabel optimize:clear
```

Routes with closures cannot be cached. Use controller handlers before enabling
route cache in production.

## Queue workers

Run workers under a process supervisor:

```bash
php vendor/bin/annabel queue:work
```

Use `sync` only when jobs should run inline.

When using the database queue, publish and run queue migrations before starting
workers:

```bash
php vendor/bin/annabel vendor:publish --tag=queue-migrations
php vendor/bin/annabel migrate
```

## Scheduler

Run the scheduler every minute:

```cron
* * * * * cd /app && php vendor/bin/annabel schedule:run >> /dev/null 2>&1
```

Inspect tasks with:

```bash
php vendor/bin/annabel schedule:list
```

## Web server

Set the document root to `public/`. Static files should be served directly and
all other requests should fall through to `public/index.php`.

## Release checklist

Verify each deployment step before directing production traffic to the release.

1. Install optimized Composer dependencies.
2. Build frontend assets when used.
3. Apply environment configuration.
4. Run migrations.
5. Run `php vendor/bin/annabel optimize`.
6. Restart PHP and queue workers.
7. Verify routes, logs, queues, scheduler, and the application health endpoint.
