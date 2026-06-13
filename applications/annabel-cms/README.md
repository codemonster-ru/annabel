# Xen CMS

[![Latest Version on Packagist](https://img.shields.io/packagist/v/codemonster-ru/xen.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/xen)
[![Total Downloads](https://img.shields.io/packagist/dt/codemonster-ru/xen.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/xen)
[![License](https://img.shields.io/packagist/l/codemonster-ru/xen.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/xen)

**Xen** is a modern modular CMS based on the [Annabel](https://github.com/codemonster-ru/annabel) framework,
designed for clean architecture, simple code, and extensibility through independent modules.

## Installation

```bash
composer require codemonster-ru/xen
```

## Features

-   **Modular structure**: Each CMS component is a separate module (`Pages`, `Users`, `Admin`, etc.).
-   **Automatic module loading**: `ModuleManager` finds and boots `ModuleServiceProvider` in `app/Modules`.
-   **Minimal bootstrap**: `bootstrap/app.php` only creates the `Application` instance.
-   **Templates within modules**: Each module can have its own templates (`Views/`) and call `view('pages::home')`.
-   **Annabel compatibility**: Uses core features (service providers, container, view engine, router, etc.).

## Database Migrations

Global (project) migrations live in `database/migrations`.
Module migrations live beside their code under `app/Modules/<Module>/database/migrations`.

`bootstrap/migrationPaths.php` collects migration paths, with `database/migrations` used as the default location.

Run everything through the bundled wrapper:

```bash
php bin/database migrate
php bin/database migrate:rollback
php bin/database make:migration CreatePostsTable
php bin/database make:migration CreatePostsTable --module=Pages
```

The CLI reads the same `config/database.php` as the application, so the migrations table, database connections,
and module paths stay synchronized between HTTP and console work.

## Database Seeders

Global (project) seeders live in `database/seeds`.
Module seeders live beside their code under `app/Modules/<Module>/database/seeds`.

```bash
php bin/database seed
php bin/database make:seed RolesSeeder
php bin/database make:seed RolesSeeder --module=Auth
```

Auth module routes:

- `GET /login`, `POST /login` (guest only, CSRF protected)
- `GET /register`, `POST /register` (guest only, CSRF protected)
- `GET /profile` (auth only)
- `POST /logout` (auth only, CSRF protected)

## Author

[**Kirill Kolesnikov**](https://github.com/KolesnikovKirill)

## License

[MIT](https://github.com/codemonster-ru/xen/blob/main/LICENSE)
