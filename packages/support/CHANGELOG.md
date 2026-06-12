# Changelog

All significant changes to this project will be documented in this file.

## [1.4.2] - 2026-06-10

### Added

- Added `cache()` helper backed by `Psr\SimpleCache\CacheInterface`.
- Added `event()` helper backed by `Psr\EventDispatcher\EventDispatcherInterface`.
- Added `deprecate()` helper for standard API deprecation notices.
- Added `old()` and `errors()` helpers for flashed validation state.

### Changed

- Raised `codemonster-ru/http` to `^2.1` for the PSR-7 HTTP line.

## [1.4.1] - 2026-01-03

### Changed

- Added optional `$cast parameter to global `env()`helper to enable typed casting via`Env::getCast()`.

## [1.4.0] - 2026-01-02

### Changed

- Bumped `codemonster-ru/http` to `^2.0`.
- Bumped `codemonster-ru/session` to `^2.0`.

## [1.3.1] - 2026-01-02

### Changed

- Guarded database helpers against redeclaration.
- `json()` helper now uses the container response instance for consistency.
- Added explicit return types to `abort()` and `dump()` helpers.
- Bumped `codemonster-ru/database` to `^2.0`.
- Session helper now distinguishes between "get" and "set" by argument count.
- `dd()` now exits with status code 0.
- Updated package metadata to reflect Annabel usage.

### Fixed

- Cleaned up changelog encoding artifacts and restored ASCII formatting.
- Suppressed debug output in `DumpHelperTest`.
- Added test coverage for null session values and empty/zero request inputs.

## [1.3.0] - 2025-12-09

### Added

- **Global database helpers** integrating the `codemonster-ru/database` package:
    - `db()` - returns the active database connection
    - `schema()` - returns schema builder for the selected connection
    - `transaction()` - executes callbacks inside a DB transaction
- Full PHPUnit test coverage for:
    - `db()` helper
    - `schema()` helper
    - With isolated `DatabaseManager` and fake container bindings
- Support for SQLite, MySQL and future drivers automatically via `ConnectionInterface::schema()`.

### Changed

- `SupportFakeContainer` updated for correct behavior with database helpers:
    - `singleton()` is now **eager**, ensuring immediate instance creation
    - Improved compatibility with Annabel-style lazy container contracts
    - Fully stable integration with request/view/db helpers
- Strengthened test isolation:
    - `reset()` now clears both bindings and instances
    - All tests run without requiring the Annabel framework

### Fixed

- `schema()` test failing due to instantiation of an abstract Grammar class.
    Now resolved automatically by the database package selecting proper driver grammar.
- DB helper now correctly bootstraps connection configuration even without other framework components.

## [1.2.0] - 2025-11-24

### Added

- Added unit tests for the global `db()` helper, including verification of passing a connection name and returning a `Connection` instance.
- Added the `FakeConnection` and `FakeDatabaseManager` classes to tests to isolate behavior and enable stable testing without a real database.
- Added the `reset()` method to `SupportFakeContainer` to clean up the container between tests.
- Improved testing infrastructure: tests are now fully isolated and independent of the Annabel framework.

## [1.1.0] - 2025-11-16

### Added

- New `abort()` helper for throwing HTTP-like exceptions with a status code.
- Introduced `HttpStatusExceptionInterface` extending `Throwable` for consistent type support.
- Added test coverage for `abort()` including status code and message handling.

### Updated

- Documentation: added `abort()` to the list of provided helpers.

## [1.0.0] - 2025-10-28

### Added

- Global helper functions for the entire **Codemonster PHP ecosystem**:
    - `config()` - access or modify configuration values
    - `env()` - read environment variables
    - `dump()`, `dd()` - elegant variable dumping
    - `request()` - get current HTTP request or input
    - `response()`, `json()` - send structured responses
    - `router()` / `route()` - define and access routes
    - `session()` - read or write session data
    - `view()` / `render()` - render templates and return responses
- Seamless integration with the **Annabel** framework via `app()` container
- Standalone compatibility when Annabel is not installed
- Full PHPUnit test coverage for all helpers (18 tests / 25 assertions)
- Support for **PHP 8.2 - 8.4** and PHPUnit **9-12**
