# Annabel

Annabel is the official monorepo for the Codemonster PHP framework ecosystem.

## Structure

```text
packages/
  framework/   codemonster-ru/annabel
  support/     codemonster-ru/support
  http/        codemonster-ru/http
  router/      codemonster-ru/router
  view/        codemonster-ru/view
  view-php/    codemonster-ru/view-php
  view-ssr/    codemonster-ru/view-ssr
  razor/       codemonster-ru/razor
  config/      codemonster-ru/config
  env/         codemonster-ru/env
  database/    codemonster-ru/database
  session/     codemonster-ru/session
  errors/      codemonster-ru/errors
  security/    codemonster-ru/security
  dumper/      codemonster-ru/dumper
  ssr-bridge/  codemonster-ru/ssr-bridge

skeleton/
  annabel-skeleton/ codemonster-ru/annabel-skeleton
```

The framework package `codemonster-ru/annabel` lives in `packages/framework`.
The application skeleton lives in `skeleton/annabel-skeleton`.

## Development

The root `composer.json` declares path repositories for `packages/*`. Package
manifests are kept inside their package directories so they can still be
published as independent Composer packages.

Architecture and release rules:

- [Architecture](docs/ARCHITECTURE.md)
- [Release policy](docs/RELEASE.md)

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
`codemonster-ru/annabel-skeleton`.

Split repositories are publishing mirrors and should be treated as read-only.
Make code changes in this monorepo only. Public Packagist packages should point
to the split repositories, including `codemonster-ru/annabel`, which should use
the `codemonster-ru/annabel-framework` split repository as its source.

To update `main` branches in split repositories without releasing a version,
run the `Split Packages` workflow manually from GitHub Actions.

## Docker

Build the PHP development image:

```bash
docker compose build php
```

Start the demo application and database:

```bash
docker compose up -d web phpmyadmin
```

The web service installs the skeleton with `skeleton/annabel-skeleton/composer.dev.json`,
which points Composer at the local packages in this monorepo.

Open the skeleton application:

```text
http://localhost:8000
```

Open phpMyAdmin for the MySQL database:

```text
http://localhost:8080
```

Default database credentials:

```text
server: db
database: annabel
username: annabel
password: annabel
```

Install and test a package from inside the container:

```bash
docker compose run --rm php composer --working-dir=packages/framework config repositories.monorepo '{"type":"path","url":"../*","canonical":false,"options":{"symlink":true}}'
docker compose run --rm php composer --working-dir=packages/framework update --prefer-dist
docker compose run --rm php composer --working-dir=packages/framework test
```
