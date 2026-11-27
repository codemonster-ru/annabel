# Contributing to Annabel

Use [GitHub Issues](https://github.com/codemonster-ru/annabel/issues) to report a
reproducible bug or propose a change. Include the affected Composer package, its
version, and your PHP version. For a suspected vulnerability, follow the
[security policy](./SECURITY.md).

The Annabel monorepo is the source for the framework, shared packages, starter,
and CMS. Open issues and pull requests here. The package repositories are
publishing mirrors updated by the [split workflow](./.github/workflows/split.yml).

## Set up the workspace

Use PHP 8.2 or later and Composer 2. Run `composer install` from the monorepo
root. Install development dependencies in each package you plan to test. The
[architecture guide](./maintenance/ARCHITECTURE.md) explains how packages use
local sibling sources and where framework integration belongs.

## Make and verify a change

- Add a focused test for a behavior change. For example, to verify a framework
  change, run `composer install --working-dir=packages/framework`,
  `composer test --working-dir=packages/framework`, and
  `composer analyse --working-dir=packages/framework`.
- For PHP changes, run `composer format:check`. For Composer manifest changes,
  run `composer validate:packages` and `composer architecture`.
- For public API changes, run `composer api:contract` and `composer api:snapshot`.
  For an intentional signature change, update the snapshot with
  `composer api:snapshot:update` and describe the migration in the pull request.
- For CMS changes, use Node.js 22 as in [CI](./.github/workflows/tests.yml). Run
  `composer install:cms`, `composer test:cms`, and `composer analyse:cms`.
  For changes that affect a starter application's boot or request flow, run
  `composer test:ecosystem`.

Use a concise English commit subject in the imperative mood and sentence case.
Omit Conventional Commits prefixes and the final period. For example,
`Document session timeout settings`.

In the pull request, describe the change, the affected packages, and the checks
you ran. Before preparing a release, run `composer quality` and follow the
[release policy](./maintenance/RELEASE.md).
