---
title: "Directory structure"
description: "Default directories in an Annabel application"
order: 4
---

# Directory structure

The skeleton keeps framework bootstrap, application code, configuration, routes,
views, and runtime storage separate.

## Application code

These directories contain classes owned by the application and its service
providers.

- `app/Controllers`: HTTP controllers.
- `app/Models`: application models.
- `bootstrap/providers`: application service providers.

## Bootstrap and public entry

Bootstrap files assemble the application, while `public/` exposes the web entry
point.

- `bootstrap/app.php`: creates and configures the application.
- `public/index.php`: front controller served by the web server.

The web server document root should be `public/`.

## Configuration

Configuration files define framework services and application defaults.

- `config/app.php`: application and provider settings.
- `config/database.php`: database connections.
- `config/session.php`: session driver and cookie settings.
- `config/cache.php`: cache stores.
- `config/security.php`: CSRF and throttling.
- `config/queue.php`: queue connections.
- `config/mail.php`: mailers and transports.

## Routes

Route files register HTTP endpoints and scheduled tasks.

- `routes/web.php`: HTTP routes.
- `routes/schedule.php`: scheduled tasks.

## Resources

Resources contain source templates and frontend assets.

- `resources/views`: PHP views.
- `resources/css`: CSS entries.
- `resources/js`: JavaScript entries.

## Runtime files

Runtime directories hold logs, caches, sessions, and other generated state.

- `storage/`: logs, cache files, sessions, and application storage.
- `bootstrap/cache/`: generated framework cache files.

These paths must be writable by the PHP process and should not be committed
with generated runtime contents.
