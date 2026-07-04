# Annabel CMS

> [!IMPORTANT]
> This split repository is read-only.
>
> Development happens in the [Annabel monorepo](https://github.com/codemonster-ru/annabel)
> under `applications/annabel-cms`. Issues and pull requests should be opened there.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/codemonster-ru/annabel-cms.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/annabel-cms)
[![License](https://img.shields.io/packagist/l/codemonster-ru/annabel-cms.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/annabel-cms)

Annabel CMS is the official modular content management system built on the
[Annabel framework](https://github.com/codemonster-ru/annabel).

## Installation

```bash
composer create-project codemonster-ru/annabel-cms
```

The release contains the compiled CMS assets, so Node.js is not required to
install or run the CMS.

## Features

- Modular structure with explicit manifests, dependencies, routes, views,
  providers, and assets.
- Automatic module discovery and deterministic lifecycle management.
- Module-owned PHP, database, templates, Vue source, and build configuration.
- Authentication, role-based admin access, CSRF protection, and configurable sessions.
- Shared HTTP and console configuration for migrations and seeders.

## Module Structure

```text
app/Modules/Example/
├── Controllers/
├── Models/
├── database/
├── resources/
├── routes/
├── views/
├── module.php
└── ModuleServiceProvider.php
```

## Development

Inside the Annabel monorepo:

```bash
COMPOSER=composer.dev.json composer update
npm ci
npm run build
COMPOSER=composer.dev.json composer test
COMPOSER=composer.dev.json composer analyse
```

The development manifest symlinks Annabel packages from `../../packages/*`.
The public `composer.json` contains stable Packagist constraints and is used by
the split repository. Rebuild and commit `public/admin/assets` and
`public/setup/assets` whenever the CMS frontend changes.

Use the root monorepo Docker environment and quality commands when changing
Annabel packages together with the CMS.

## Database

Migrations and seeders belong to the module that owns the schema. Module
database files live under `app/Modules/<Module>/database/`.

CMS installation runs the required migrations automatically. Module migrations
belong to the module installation and update lifecycle, not to a separate
manual user step. Use the framework console only for development, CI, or
maintenance tasks.

## Deployment

Use `SESSION_DRIVER=redis` for horizontally scaled deployments. The default
file driver is appropriate for single-node development.

## License

[MIT](https://github.com/codemonster-ru/annabel-cms/blob/main/LICENSE)
