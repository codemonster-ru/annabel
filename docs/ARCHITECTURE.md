# Annabel Architecture

Annabel is a small framework assembled from independently installable packages.
The framework package wires the ecosystem together, but leaf packages must stay
usable without depending on `codemonster-ru/annabel`.

## Package Boundaries

- `codemonster-ru/annabel` is the composition layer: application lifecycle,
  container, providers, kernel, console, publishing, cache, validation, logging.
- Domain packages (`http`, `router`, `view`, `session`, `security`, `database`,
  `env`, `config`, `errors`, `dumper`, `razor`, `ssr-bridge`) own their runtime
  behavior and must not depend on the framework package.
- `codemonster-ru/support` contains convenience helpers. Helpers may resolve
  framework services, but package core code should prefer explicit dependencies.
- The skeleton is an application template. It may extend framework providers, but
  it must not ship generated dependencies, cache, storage, or local lock files.

## Local Package Development

Every package manifest declares the monorepo sibling packages as a Composer path
repository (`../*`) with symlinks enabled. This keeps local package development
using the checked-out `codemonster-ru/*` sources instead of stale Packagist
archives when a package is installed or tested as the root project.

Composer only honors `repositories` from the root project. Once a package is
published and consumed as a dependency, its local path repository declaration is
ignored, and downstream applications resolve `codemonster-ru/*` packages from
their own configured repositories, normally Packagist.

## Security Boundary

`security` is a framework-agnostic package. It exposes CSRF and rate-limiting
services/middleware, while Annabel provides the framework service provider that
binds those services into an application. This keeps the dependency direction
honest: Annabel uses security; security does not know about Annabel.

## Public API Policy

Public API is what an application or external package can reasonably depend on:
contracts, service classes, middleware, engines, models, query builders,
exceptions, and extension points. Internal API is implementation machinery:
bootstrap discovery, CLI command implementations, generated manifests, lazy
adapters, and process helpers.

Breaking changes to public API require a major version bump or a documented
migration path. Internal API may change in minor releases, but should still be
kept readable and covered by tests because framework code depends on it.

The public/internal classification is enforced by `composer api:contract`.
Every class, interface, or trait under `packages/*/src` must be covered by that
contract, and public API must not reference another package's internal classes.
The public API surface is captured by `composer api:snapshot`; intentional
public signature changes must update `docs/api-snapshot.json`.

## Release Gates

`composer quality` is the release gate:

- Composer validation for every package and the skeleton.
- Architecture dependency boundary check.
- API contract check.
- API snapshot check.
- Project hygiene check for package metadata and skeleton artifacts.
- PHPUnit for every package.
- Ecosystem smoke test against a temporary skeleton installation, including
  request, middleware, validation, session, and response flow.
- PHPStan at `level: max` for every package.
- Psalm for packages that opt in.
- Composer security audit.
