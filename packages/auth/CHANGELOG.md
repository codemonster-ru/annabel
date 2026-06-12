# Changelog

All notable changes to `codemonster-ru/auth` will be documented in this file.

## [1.0.1] - 2026-06-10

- Require `codemonster-ru/session ^2.0.1` because logout invalidation depends on that API.

## [1.0.0] - 2026-06-10

- Added framework-agnostic authentication contracts.
- Added native password hashing.
- Added array user provider for simple apps and tests.
- Added session guard and auth middleware.
- Session guard logout invalidates the session by default.
