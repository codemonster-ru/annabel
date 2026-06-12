---
title: "Upgrade guide"
description: "How to approach Annabel upgrades"
order: 7
---

# Upgrade guide

Annabel packages follow Semantic Versioning after `1.0.0`. Framework releases
must be tested against the ecosystem and skeleton before tagging.

## Before upgrading

1. Read the affected package changelogs.
2. Review public API changes when upgrading across major versions.
3. Run the application test suite.
4. Clear generated caches after dependency and config changes.

## Update dependencies

```bash
composer update codemonster-ru/annabel --with-dependencies
```

For skeleton applications, also review changes to config files, migrations,
providers, Vite setup, and Composer scripts.

## Clear generated caches

```bash
php vendor/bin/annabel optimize:clear
```

Then rebuild production caches during deployment:

```bash
php vendor/bin/annabel optimize
```

## Breaking changes

Breaking public API changes require a major version bump or a documented
migration path. Internal framework API may change in minor releases, but
application code should avoid depending on internal classes.

## Deprecated APIs

When a package deprecates an API, migrate to the documented replacement before
the next major release. Deprecations should be covered in the package
changelog.

## Package mirrors

Split repositories are publishing mirrors. Make code changes in the Annabel
monorepo and consume released package tags from Composer.
