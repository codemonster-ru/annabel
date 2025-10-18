# Changelog

All notable changes to **codemonster-ru/annabel** will be documented in this file.

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
