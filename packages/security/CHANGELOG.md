# Changelog

All notable changes to this package will be documented in this file.

## [1.1.0] - 2025-12-28

### Added

-   Trusted proxy support for rate limiting and IPv6 CIDR matching.
-   Database and Redis throttle storages with atomic increments.
-   `AtomicThrottleStorageInterface` and atomic `RateLimiter::attempt()` flow.
-   Throttle presets with login (IP + account) support and `RateLimit-*` headers.
-   Migration for throttle table and optional E2E tests for MySQL/Redis.

### Changed

-   Rate limiting key now uses request path (without query) by default.
-   `csrf_field()` respects configured input key; `csrf_token()` tries DI container when available.

### Removed

-   `LoginThrottleRequests` middleware (use `ThrottleRequests` with `login` preset).

### Upgrade notes

-   If you used `LoginThrottleRequests`, switch to `ThrottleRequests` with the `login` preset.
-   For multi-node deployments, configure `storage` as `database` or `redis`.
-   If you set a custom table name, ensure the migration uses `security.throttle.table`.

## [1.0.0] - 2025-12-17

### Added

-   CSRF: `Codemonster\Security\Csrf\VerifyCsrfToken`, token manager `Codemonster\Security\Csrf\CsrfTokenManager`, helpers `csrf_token()` and `csrf_field()`.
-   Rate limiting: `Codemonster\Security\RateLimiting\ThrottleRequests`, `Codemonster\Security\RateLimiting\RateLimiter`, storage contracts and `SessionThrottleStorage`.
-   Integration with Annabel: `Codemonster\Security\Providers\SecurityServiceProvider`.
-   Tests for CSRF and rate limiter.
