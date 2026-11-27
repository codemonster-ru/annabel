# Annabel CMS

> [!IMPORTANT]
> This split repository is read-only.
>
> Development happens in the [Annabel monorepo](https://github.com/codemonster-ru/annabel)
> under `applications/annabel-cms`. Issues and pull requests should be opened there.

Annabel CMS is the official modular content management system built on the
[Annabel framework](https://github.com/codemonster-ru/annabel).

## Get started

See [GitHub Releases](https://github.com/codemonster-ru/annabel-cms/releases) for published source archives.
For a local development setup, use the [Annabel CMS workspace](https://github.com/codemonster-ru/annabel/tree/main/applications/annabel-cms)
and the commands in Development.

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

The development manifest symlinks Annabel packages from `../../packages/*` and installs CodeMonster
UI from Packagist. Run `COMPOSER=composer.dev.json composer assets:ui` to republish the
integrity-checked token and component styles without Node.js; install and update commands run the
same publication step automatically. The npm manifest installs exact stable CodeMonster UI versions
from the npm registry.
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

## Support and contributing

Read the [contribution guide](https://github.com/codemonster-ru/annabel/blob/main/CONTRIBUTING.md) before proposing a change. Report vulnerabilities through the [security policy](https://github.com/codemonster-ru/annabel/blob/main/SECURITY.md).

## License

[MIT](https://github.com/codemonster-ru/annabel-cms/blob/main/LICENSE)
