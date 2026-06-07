# Release Policy

Annabel packages follow Semantic Versioning once a package reaches `1.0.0`.

## Versioning

- `MAJOR`: breaking public API changes, removed deprecated APIs, or changed
  behavior that existing applications cannot reasonably absorb automatically.
- `MINOR`: backward-compatible features, new public APIs, and deprecations with
  a documented replacement.
- `PATCH`: bug fixes, documentation, tests, internal refactors, and security
  fixes that preserve public API compatibility.

Packages may be released independently, but a framework release must be tested
against the full ecosystem and skeleton with `composer quality`.

## Public API

Public API is defined by `composer api:contract` and captured by
`composer api:snapshot`. It includes public package contracts, framework
extension points, middleware, engines, models, query builders, exceptions, and
global helpers, plus public/protected methods on non-internal classes.

Internal API is implementation detail. It may change in minor releases, but
should stay tested because framework code depends on it.

Parameter names are not part of Annabel's compatibility promise unless a method
explicitly documents named-argument support. Parameter order, requiredness,
types, defaults, visibility, return types, class inheritance, implemented
interfaces, public/protected properties, and public/protected constants are part
of the snapshot. Global helper signatures are also part of the snapshot.

## Deprecations

Prefer a deprecation before a breaking change:

1. Add the replacement API.
2. Keep the old API working.
3. Trigger a deprecation notice with `deprecate($package, $version, $message)`.
4. Add or update tests that assert the deprecation notice.
5. Document the deprecation in the package changelog.
6. Remove the deprecated API only in the next major release.

Security fixes may introduce a breaking change if there is no safe compatible
alternative. Such releases must call out the break explicitly.

## Release Checklist

Before tagging a release:

1. Run `composer quality`.
2. Review API snapshot changes. If intentional, run
   `composer api:snapshot:update` and document the change.
3. Update affected package changelogs.
4. Verify the skeleton remains clean with `composer project:hygiene`.
5. Tag packages with immutable `vMAJOR.MINOR.PATCH` tags.
