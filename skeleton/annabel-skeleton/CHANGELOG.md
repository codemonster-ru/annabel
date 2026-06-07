# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

### Changed

-   Updated the security provider base class to `Codemonster\Annabel\Providers\SecurityServiceProvider`.
-   Bumped `codemonster-ru/annabel` constraint to `^1.15`.

### Added

-   Added `config/app.php` provider registry.
-   Added `config/logging.php` with file logging defaults.
-   Added `config/cache.php` with file and array cache stores.
-   Added `config/validation.php` with sensitive old-input filtering defaults.
-   Added package provider discovery and manifest cache settings to `config/app.php`.
-   Documented package resource publishing through `vendor:publish`.
-   Added PSR-14 event dispatcher support through the framework.

## [1.3.0] - 2026-01-03

### Changed

-   Enabled throttle middleware auto-registration by default and synced `.env.example` default.
-   Bumped `codemonster-ru/security` to `v1.1.0`.

## [1.2.0] - 2025-12-17

### Added

-   Integrated `codemonster-ru/security` (CSRF + rate limiting).
-   Added `bootstrap/providers/SecurityServiceProvider.php` to auto-register security middleware in Annabel.
-   Added `config/security.php` with default CSRF/throttle settings.
-   Added `SECURITY_*` environment variables to `.env.example` for toggling security features per environment.

### Dependencies

-   Added `codemonster-ru/security` (`v1.0.0`).
-   Updated `composer.lock` (includes `codemonster-ru/database` `v1.4.3`).

## [1.1.0] - 2025-12-10

### Added

-   Added `config/database.php` with default MySQL/SQLite connections and migrations path.
-   Implemented users migration (`database/migrations/2025_12_10_213456_create_users_table.php`) with standard auth columns.

## [1.0.1] - 2025-11-10

### Changed

-   Updated Response import to `Codemonster\Http\Response` (`app/Controllers/HomeController.php`).
-   Removed `declare(strict_types=1)` from `public/index.php`, `bootstrap/app.php`, `routes/web.php`, `app/Controllers/HomeController.php`.
-   Removed redundant file-level docblocks in the same files.

### Dependencies

-   Updated `composer.lock` (compatible with `codemonster-ru/annabel ^1.0`).

## [1.0.0] - 2025-10-18

### Initial Release

-   Introduced the **Annabel Skeleton** - starter project for the [Annabel PHP framework](https://github.com/codemonster-ru/annabel)
-   Included minimal but complete structure:
    -   `app/Controllers/HomeController.php`
    -   `bootstrap/app.php`, `public/index.php`
    -   `routes/web.php`, `resources/views/home.php`
-   Integrated example controller and default view template
-   Added built-in dev server command `composer start`
