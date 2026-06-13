# Changelog

## [Unreleased]

### Fixed

- Fixed admin asset output paths to avoid nested `/admin/assets/assets/...` URLs.
- Added an SVG favicon for the admin shell.

## [1.0.0] - 2026-06-13

### Changed

-   Renamed Xen CMS to Annabel CMS.
-   Moved canonical development into the Annabel monorepo.
-   Renamed the Composer package to `codemonster-ru/annabel-cms`.
-   Renamed the PHP namespace to `Codemonster\Cms`.
-   Added a monorepo development manifest backed by local Annabel packages.
-   Added compiled admin assets to the release artifact.
-   Updated Docker and Dev Container startup to install and build frontend dependencies.

### Removed

-   Removed the `Codemonster\Xen` namespace and Xen-specific configuration names.

## [0.4.0] – 2026-01-22

### Changed

-   ModuleManager now discovers modules by directory and supports modules without a `ModuleServiceProvider` by auto-wiring view namespaces and routes.
-   Module listing now reports modules without providers as `(auto)`.

### Removed

-   Removed Admin, Auth, and Pages `ModuleServiceProvider` classes in favor of auto module wiring.

## [0.3.0] – 2026-01-07

### Added

-   Auth module with registration, login, profile, and logout flows.
-   Auth models for users/roles with role assignment helpers.
-   Auth module migrations for users, roles, and role_user pivot tables.
-   Roles seeder for default `user` and `admin` roles.
-   Auth views for login, register, and profile (CSRF-ready forms).
-   Database CLI wrapper (`bin/database`) plus migration/seed path bootstrap helpers.
-   Database and security configuration files.
-   ORM service provider to bind model connections.
-   Security service provider wrapper.
-   Global migrations/seeds folders with `.gitkeep` placeholders.

### Changed

-   Simplified `ModuleServiceProvider`: removed router controller factory binding, as this is now handled by Annabel core.
-   AuthMiddleware now receives an explicit access context `'admin'` when applied to the Admin route.
-   Admin access now uses strict auth validation (DB check every request) to protect `/admin`.
-   Registration now runs in a transaction; failures no longer leave orphaned users.
-   Auth routes now use `/login`, `/register`, `/profile`, and `/logout` with middleware and throttling.
-   Auth login view now uses email/password fields and CSRF token.
-   View namespaces standardized to lowercase `views` paths across modules.
-   Pages home view copy now reflects the lowercase `views` path.
-   README expanded with migrations, seeders, and auth route documentation.
-   `.env` is now ignored by git.
-   Updated dependencies (Annabel ^1.14, Security ^1.1).

### Fixed

-   Session auth now re-validates users on a TTL to avoid stale logins in guest/auth middleware.
-   Registration errors surface in debug mode; production keeps a generic message.
-   User/Role hydration now preserves `id` by allowing it in `fillable`.

### Removed

-   Legacy admin-only `LoginController` and `/admin/login` route.

## [0.2.0] – 2025-10-19

### Added

-   **Modular architecture** — Xen now loads multiple independent modules (`Core`, `Admin`, `Pages`) with their own routes, controllers, and views.
-   **Controller factory integration** — controllers are now automatically resolved through Annabel’s container.
-   **Admin module** — introduced the `/admin` area with a `DashboardController` and middleware-ready routing.
-   **Core module routing** — system routes are now registered through `ModuleServiceProvider`, ensuring clean initialization order.
-   **Router helpers** — added global `router()` and `route()` functions for concise route registration.
-   **View modular loading** — module-specific templates can now be resolved automatically through namespace mapping.

### Changed

-   Updated bootstrap flow — the CMS boot process now initializes the router before module bootstrapping.
-   Improved internal service provider logic for better module isolation and loading order.
-   Simplified module routing: all module `web.php` files are automatically loaded if they exist.

### Fixed

-   Fixed early `Router instance not available` errors during module boot.
-   Fixed missing controller dependencies when dispatching through the router.
-   Fixed trailing slash inconsistencies in route matching (`/admin` and `/admin/` now resolve identically).

## [0.1.0] – 2025-10-18

### Added

-   **Xen Core Module** — a basic system module that automatically initializes all other CMS modules.
-   **ModuleManager** — dynamically finds and loads all `ModuleServiceProvider` in `app/Modules/*`.
-   **Automatic Bootstrapping** — `bootstrap/app.php` is now minimal, and the Xen core is automatically included.
-   **View namespaces** — added support for `$view->addNamespace()` for templates within modules.
-   **Pages module example** — added a basic `Pages` module with the `/` route and the `pages::home` template.

### Changed

-   Simplified startup: the CMS now fully loads with `composer start`, eliminating the need to manually register modules.
-   The Annabel framework is integrated with Xen via the `Core` system module.

### Fixed

-   Fixed issues with 404 errors on startup and incorrect module loading.
-   Fixed `Undefined array key` errors when searching for `namespace` in `ModuleManager`.
