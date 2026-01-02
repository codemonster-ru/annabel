# Changelog

All notable changes to **codemonster-ru/annabel** will be documented in this file.

## [1.14.0] - 2026-01-03

### Added

-   Added guard against reinitializing the Application singleton and tests for it.
-   Added parameter support for `Application::make()` and helper tests for parameterized resolution.
-   Added middleware validation for missing controller methods and tests for role vs group middleware.
-   Added validation for multiple classes in custom provider files.

### Changed

-   Container now supports parameterized resolution for closures and throws when reusing singletons with new parameters.
-   Middleware normalization handles role-based entries without misclassifying them as groups.
-   Provider class resolution now uses token parsing instead of regex.
-   Removed duplicate `.env` loading from CoreServiceProvider.
-   Updated README with container parameter usage and Application singleton notes.
-   Updated `codemonster-ru` dependencies: support ^1.4, errors ^1.2, view-php ^2.1, razor ^1.1.

## [1.13.0] - 2025-12-22

### Added

-   Added support for database CLI seed commands (`make:seed`, `seed`) when `codemonster-ru/database` is installed.
-   Added support for database maintenance commands (`db:wipe`, `db:truncate`) in the Annabel CLI.
-   Added console tests to verify registered commands and aliases.

### Changed

-   Updated README with the latest database CLI commands.

## [1.12.0] - 2025-12-10

### Added

-   New `annabel` CLI entry point (`php vendor/bin/annabel`) with built-in help output inspired by Laravel's artisan, including colorized terminal formatting.
-   Added initial CLI commands: `about`, `route:list`, `config:get`, `container:list`, and `serve`.
-   When `codemonster-ru/database` is installed, Annabel CLI now auto-loads database commands (`make:migration`, `migrate`, `migrate:rollback`, `migrate:status`) via the package's CLI kernel.
-   Database CLI integration is now lazy: migration repository/connection initialize only when commands run, so commands are visible even without an active DB connection.

## [1.11.0] – 2025-12-09

### Added

-   **First-class database integration** with the `codemonster-ru/database` package:

    -   Added `DatabaseServiceProvider` with proper container bindings for:
        -   `DatabaseManager`
        -   automatic connection resolution
        -   runtime grammar selection (MySQL, SQLite, extensible structure)
    -   Added container binding for the Schema builder (`Schema`), enabling use of:
        -   `schema()->create()`
        -   `schema()->table()`
        -   `schema()->drop()`
    -   Automatic loading of DB configuration via `config('database')`.

-   **Support for global helpers from codemonster-ru/support**, including:
    -   `db()` — returns the current database connection
    -   `schema()` — returns schema builder for the selected connection
    -   `transaction()` — executes code inside a DB transaction

### Changed

-   `Bootstrapper` updated to load the new `DatabaseServiceProvider`
    before view and session providers for correct dependency ordering.
-   Standardized provider boot order for proper initialization of config,
    database, view, session, and router layers.

### Fixed

-   Eliminated errors caused by instantiating schema grammars directly from Annabel.
-   Corrected container resolution of database-related classes when running without Annabel (standalone mode).
-   Improved stability of the application container when resolving database singletons.

### Improvements

-   Cleaner dependency injection for all DB components.
-   Better separation between Annabel core and database layer (no hard dependency on MySqlGrammar inside the framework).
-   Improved developer experience for Xen and other Annabel-based modules using database operations.

## [1.10.0] - 2025-11-16

### Added

-   Support for parameterized route middleware in `Kernel::runRoute()`.  
    Middleware can now receive additional arguments (e.g. role names).

### Changed

-   Middleware pipeline logic updated to match the new structure from
    `codemonster-ru/router`, where middleware items are stored as arrays:
    `[MiddlewareClass, argument]`.

### Fixed

-   Resolved "Argument #1 must be of type string, array given"
    when executing middleware with parameters.
-   Ensured consistent instantiation and execution of middleware classes.

### Improvements

-   Cleaner and more explicit middleware invocation.
-   Better compatibility between Annabel and Xen framework modules.

## [1.9.1] - 2025-11-16

### Fixed

-   Fixed incorrect behavior of the SessionServiceProvider, which caused multiple Store instances to be created, leading to session data loss.
-   Fixed an issue where sessions were not persisted between requests (due to an update to the `codemonster-ru/session` package).
-   `session()` now always returns a single Store, and `boot()` correctly starts the session via `$store->start()`.

### Changed

-   Session initialization logic in Annabel has been aligned with the new architecture of the `codemonster-ru/session` package.

## [1.9.0] - 2025-11-16

### Added

-   Added automatic loading of the .env file via the codemonster-ru/env package during the bootstrap phase.
-   Support for application configuration via environment variables (APP_DEBUG, etc.).
-   All Annabel services, as well as the codemonster-ru/errors, codemonster-ru/view, and codemonster-ru/xen packages, can now correctly use env().

### Changed

-   The Bootstrapper::run() method has been updated: loading of .env now occurs before ErrorHandler initialization, ensuring correct debug mode detection.
-   Improved integration with codemonster-ru/errors: detailed debug pages are now automatically enabled when APP_DEBUG=true.

### Fixed

-   Fixed an issue where `APP_DEBUG=true` was ignored, and the generic error page was always displayed.
-   Fixed cases where ExceptionHandler would not work correctly due to missing env variables.

## [1.8.1] - 2025-11-16

### Fixed

-   Fixed an issue with exception handling in Bootstrapper: Errors are now output using `Response::send()` instead of a direct `echo` call, preventing errors like "Call to undefined method Response::getBody()" and correctly sending response headers and status.
-   Fixed the behavior of the global exception handler—it is now fully compatible with the `codemonster-ru/errors` package and the new error handling architecture.

## [1.8.0] - 2025-11-16

### Changed

-   The HTTP Kernel has been redesigned to integrate with the updated Router architecture (match-only).
-   The Kernel is now fully responsible for executing controllers, building the middleware pipeline, and generating Responses.
-   The handler invocation logic has been moved from the Router and Dispatcher to the Kernel.
-   Improved error handling: The Kernel now correctly passes exceptions to the ExceptionHandler, even if they occur within middleware or a controller.
-   The structure of the `dispatch()` method has been optimized; it now works only with Routes and delegates execution to `runRoute()`.

### Added

-   The `runRoute()` method has been added—a single point of execution for controllers and middleware.
-   Support for the Annabel DI container when creating controllers and middleware.
-   Support for route-middleware at the Route object level.

### Fixed

-   Fixed an issue where Router would return the result of executing handler instead of Route, which would break the Kernel architecture.
-   Fixed the middleware execution order (it now correctly wraps the controller, as in Laravel).
-   Fixed bugs related to empty Response and rendering errors when there was no content.

## [1.7.0] - 2025-11-10

### Added

-   Integrated `codemonster-ru/errors` as the default error handling package.
-   Global exception handler via `set_exception_handler` in `Bootstrap/Bootstrapper` to render all uncaught exceptions.
-   Binding of `Codemonster\\Errors\\Contracts\\ExceptionHandlerInterface` to `SmartExceptionHandler` with view-based renderer.
-   Composer dependency: `"codemonster-ru/errors": "^1.0"`.

### Changed

-   `src/Http/Kernel.php`: delegates exceptions and HTTP errors to the registered `ExceptionHandlerInterface` and normalizes empty 4xx/5xx bodies through the handler.
-   `src/Providers/CoreServiceProvider.php`: registers the new error handler and passes a renderer that uses the framework `View`.
-   Behavior respects `APP_DEBUG` to toggle detailed error pages.

### Removed

-   `src/Contracts/ExceptionHandlerInterface.php`
-   `src/Exceptions/DefaultExceptionHandler.php`
-   `src/Exceptions/DebugExceptionHandler.php`
-   `resources/views/errors/debug.php`

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
