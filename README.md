# Annabel

Annabel is the official monorepo for the Codemonster PHP framework ecosystem.

## Structure

```text
packages/
  api-resource/ codemonster-ru/api-resource
  framework/   codemonster-ru/annabel
  support/     codemonster-ru/support
  http/        codemonster-ru/http
  http-client/ codemonster-ru/http-client
  router/      codemonster-ru/router
  view/        codemonster-ru/view
  view-php/    codemonster-ru/view-php
  view-ssr/    codemonster-ru/view-ssr
  razor/       codemonster-ru/razor
  cache/       codemonster-ru/cache
  config/      codemonster-ru/config
  env/         codemonster-ru/env
  events/      codemonster-ru/events
  database/    codemonster-ru/database
  session/     codemonster-ru/session
  auth/        codemonster-ru/auth
  filesystem/  codemonster-ru/filesystem
  validation/  codemonster-ru/validation
  logging/     codemonster-ru/logging
  mail/        codemonster-ru/mail
  queue/       codemonster-ru/queue
  scheduler/   codemonster-ru/scheduler
  errors/      codemonster-ru/errors
  security/    codemonster-ru/security
  dumper/      codemonster-ru/dumper
  ssr-bridge/  codemonster-ru/ssr-bridge

skeleton/
  annabel-skeleton/ codemonster-ru/annabel-skeleton

applications/
  annabel-cms/ codemonster-ru/annabel-cms
```

`packages/` contains reusable libraries published as package splits.
`skeleton/` contains starter project templates, not maintained applications.
`applications/` contains maintained applications and products.

The framework package `codemonster-ru/annabel` lives in `packages/framework`.
The application skeleton lives in `skeleton/annabel-skeleton`.
The official CMS lives in `applications/annabel-cms`.

## Development

The root `composer.json` declares path repositories for `packages/*`. Package
manifests are kept inside their package directories so they can still be
published as independent Composer packages.

Architecture and release rules:

- [Architecture](maintenance/ARCHITECTURE.md)
- [Release policy](maintenance/RELEASE.md)

Run the full release gate before tagging or splitting packages:

```bash
composer quality
```

## Package Splits

Package repositories are updated from this monorepo by the `Split Packages`
workflow. The workflow requires a `MONOREPO_SPLIT_TOKEN` secret with write
access to the target repositories.

All package releases use package-scoped tags in the monorepo:

```bash
git tag framework/v1.15.0
git push origin framework/v1.15.0
```

```bash
git tag support/v1.5.0
git push origin support/v1.5.0
```

The split workflow pushes the package contents and a normal Composer tag, such
as `v1.5.0`, to the target package repository. The framework package target is
`codemonster-ru/annabel-framework`; the public `codemonster-ru/annabel`
repository remains the monorepo. The skeleton target is
`codemonster-ru/annabel-skeleton`, and the CMS target is
`codemonster-ru/annabel-cms`.

Split repositories are publishing mirrors and should be treated as read-only.
Make code changes in this monorepo only. Public Packagist packages should point
to the split repositories, including `codemonster-ru/annabel`, which should use
the `codemonster-ru/annabel-framework` split repository as its source.

Every push to monorepo `main` updates the `main` branch of each split
repository. Package-scoped tags publish version tags and GitHub releases only
for the selected package.

## Annabel CMS

Install and test the CMS against symlinked monorepo packages:

```bash
composer install:cms
composer test:cms
composer analyse:cms
```

The public CMS manifest uses stable Packagist constraints. Its
`composer.dev.json` is reserved for monorepo development.

## Docker

Build and start the complete monorepo development stack:

```bash
docker compose up -d --build
```

This starts both applications, their asset watchers, one shared MySQL server,
Redis for CMS sessions, and phpMyAdmin.

| Service | URL |
| --- | --- |
| Skeleton | http://localhost:8000 |
| Skeleton Vite | http://localhost:5173 |
| Skeleton phpMyAdmin | http://localhost:8080 |
| Annabel CMS | http://localhost:8001 |
| Annabel CMS admin | http://localhost:8001/admin |

MySQL is published on `3307`. Skeleton and CMS use separate databases on that
server so their `users`, `migrations`, and other application tables cannot
collide.

The `skeleton` service installs the skeleton with `skeleton/annabel-skeleton/composer.dev.json`,
which points Composer at the local packages in this monorepo. Development
dependencies are symlinked from `packages/*`, so changes in packages are visible
in the browser without publishing them first.

The `skeleton-assets` service provides skeleton HMR. The `cms-assets` service
watches the admin Vue and CSS sources and rebuilds the CMS manifest
automatically; refresh `/admin` to load the rebuilt bundle.

The `cms` service uses `applications/annabel-cms/composer.dev.json`, so its
Annabel dependencies are also symlinked from `packages/*`.

The root Compose file is the monorepo orchestration layer.

Database credentials:

```text
server: db
username: annabel
password: annabel
skeleton database: annabel
CMS database: annabel_cms
```

Install and test a package from inside the container:

```bash
docker compose run --rm php composer --working-dir=packages/framework config repositories.monorepo '{"type":"path","url":"../*","canonical":true,"options":{"symlink":true}}'
docker compose run --rm php composer --working-dir=packages/framework update --prefer-dist
docker compose run --rm php composer --working-dir=packages/framework test
```

Stop the development services:

```bash
docker compose down
```
