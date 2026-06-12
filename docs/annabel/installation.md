---
title: "Installation"
description: "How to create and run an Annabel application"
order: 2
---

# Installation

Annabel applications should start from the official skeleton. The skeleton
includes the default directory structure, configuration files, routes,
migrations, providers, Vite setup, and Composer script aliases.

## Requirements

The application and any enabled drivers require the following runtime
dependencies.

- PHP `8.2` or higher.
- Composer 2.
- PDO when using `codemonster-ru/database`.
- Node.js and npm only when building skeleton frontend assets.

Optional runtime extensions depend on enabled drivers:

- `ext-redis`: Redis cache, queue, throttling, or sessions.
- `ext-sodium`: encrypted session payloads.
- `ext-pdo_mysql`: MySQL database connections.
- `ext-pdo_sqlite`: SQLite database connections.

## Create an application

Create a project from the application skeleton and install its dependencies.

```bash
composer create-project codemonster-ru/annabel-skeleton myapp
cd myapp
cp .env.example .env
composer install
composer serve
```

## Frontend assets

The skeleton ships with Vite for CSS and JavaScript assets:

```bash
npm install
npm run dev
```

Build production assets before deployment:

```bash
npm run build
```

Views can include Vite entries through the helper:

```php
<?= vite('resources/js/app.js') ?>
```

## Run framework commands

Use the project-local framework binary to inspect and manage the application.

```bash
php vendor/bin/annabel list
php vendor/bin/annabel route:list
php vendor/bin/annabel about
```

The skeleton also exposes common commands as Composer scripts:

```bash
composer serve
composer migrate
composer optimize
composer optimize:clear
composer queue
composer schedule
```

## Direct framework install

Use `composer require codemonster-ru/annabel` only when building a custom
application structure. For normal applications, prefer the skeleton.

## Verify the application

After installation, these commands should work:

```bash
php vendor/bin/annabel about
php vendor/bin/annabel route:list
```

If the application uses MySQL, update `.env` before running migrations. For
SQLite, create the database file first:

```bash
touch database/database.sqlite
```
