# Changelog

All notable changes to **codemonster-ru/annabel** will be documented in this file.

## [1.6.0] — 2025-11-08

### Added

-   Added `Bootstrap/Bootstrapper` — a separate class responsible for the initialization process (helpers, providers, kernel, views).
-   Added the centralized contract `ExceptionHandlerInterface`.
-   Added exception handlers:
-   `DefaultExceptionHandler` — a minimal, safe handler (production).
-   `DebugExceptionHandler` — a detailed handler with an HTML page and traceback (dev).
-   Added the default error template `resources/views/errors/debug.php`, which uses `codemonster-ru/view`.

### Changed

-   `Application.php`: simplified and refactored — now delegates bootstrap to `Bootstrapper`. - `Http/Kernel.php`: Integrated with the exception system, now uses `ExceptionHandlerInterface`.
-   `ViewServiceProvider`: Now registers two template paths:

1. `resources/views` from the project;
2. `resources/views` from the Annabel framework itself.

-   Exceptions are now correctly handled and rendered via View.

### Fixed

-   Fixed the `View not found: errors.debug` error when rendering templates.
-   Fixed a collision between the `Codemonster\Http\Response` and `Codemonster\Annabel\Http\Response` classes (Annabel now inherits the base Response).
-   Eliminated potential fatal errors when a template or View is missing (the fallback is implemented in ExceptionHandler).

## [1.5.0] – 2025-10-30

### Added

-   Added `'view'` alias in the service container — now both `app('view')` and `view()` helpers work correctly across all dependent packages.

### Changed

-   Improved internal `ViewServiceProvider` registration to ensure consistent access to the `View` instance from the container.

## [1.4.0] – 2025-10-28

### Changed

-   Global helper functions (`config`, `env`, `dump`, `request`, `response`, `router`, `session`, `view`)  
    have been moved to a new shared package **`codemonster-ru/support`**.
    Annabel now automatically uses helpers from that package.
-   Simplified `Application` bootstrap — no manual helper registration required.
-   Cleaned up `src/helpers/`:
    now only `app.php` and `basePath.php` remain inside the framework core.
-   Refactored `CoreServiceProvider`:
    -   added container aliases (`'config'`, `'router'`, `'request'`) for compatibility with new helpers;
    -   standardized container bindings to match Laravel-style resolution.
-   Improved modular consistency with other Codemonster packages.

### Added

-   Automatic integration with `codemonster-ru/support` (v1.0+).
-   Full support for standalone usage of helpers via container.

### Removed

-   Legacy fallback logic for global helpers inside Annabel core.

## [1.3.0] – 2025-10-24

### Added

-   **Session integration** — Annabel now uses the new package [`codemonster-ru/session`](https://github.com/codemonster-ru/session) as its session foundation.
-   **`SessionServiceProvider`** — automatically starts and registers a session on application boot.
-   **Global helper** `session()` — provides simple access to session data anywhere.
-   **Session tests** — added SessionHelperTest to verify helper behavior and integration with the provider system.

### Changed

-   Updated `Application::registerProviders()` to include `SessionServiceProvider` in the default provider list.
-   Improved bootstrap consistency: session is now available immediately after application start.

## [1.2.0] – 2025-10-23

### Changed

-   Refactored HTTP layer: `Request` and `Response` classes moved to standalone package [`codemonster-ru/http`](https://github.com/codemonster-ru/http).
-   Updated imports in `Http\Kernel` and helper functions to use the new package.
-   Improved modularity — Annabel now relies on external HTTP foundation instead of internal implementation.

### Removed

-   Deleted redundant `tests/Http/RequestTest.php` and `tests/Http/ResponseTest.php` (these are now covered by `codemonster-ru/http` tests).

## [1.1.1] – 2025-10-19

### Fixed

-   🧩 **Router helpers initialization** — The global `router()` and `route()` functions now correctly initialize the `Router` instance, even if it has not yet been registered in the container.
-   Added a safe fallback to prevent the `RuntimeException: Router instance not available in the current application context` error.

### Improved

-   Added explicit nullable types (`?string`, `?callable|array`) for helper parameters.
-   Improved typing of return values ​​(`Router|Route`), providing better support for IDEs and static analysis (Intelephense, PHPStan).
-   Improved stability of early loading of components and helpers during application initialization.

## [1.1.0] - 2025-10-18

### ✨ Added

-   Introduced abstract class `Codemonster\Annabel\Providers\ServiceProvider`
-   Implements `ServiceProviderInterface` and defines base methods `register()` and `boot()`
-   Provides protected `$app` property and `app()` helper for convenient access to the `Application` instance

## [1.0.0] - 2025-10-17

### Added

-   Application container with dependency injection, autowiring, and singleton binding.
-   Service Provider system (CoreServiceProvider, ViewServiceProvider) for modular package registration.
-   Router integration via `codemonster-ru/router`.
-   HTTP layer with Request, Response, Kernel, and middleware support.
-   Configuration & environment loading via `codemonster-ru/config` and `codemonster-ru/env`.
-   View system integration using `codemonster-ru/view` and `codemonster-ru/view-php`.
-   Global helpers (`app`, `config`, `env`, `view`, `router`, `dump`, `dd`, `base_path`).
-   Comprehensive PHPUnit test suite for all core components.

### Improved

-   CoreServiceProvider correctly injects Application into Kernel.
-   ViewServiceProvider handles missing directories safely.
-   Unified branch alias naming (`1.0.x-dev`) across ecosystem packages.

## [0.0.5] - 2025-09-12

### Prototype Release

-   Initial prototype of the Annabel framework.
