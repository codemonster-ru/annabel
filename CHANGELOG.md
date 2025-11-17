# Changelog

## [Unreleased] – 2025-10-19

### Changed

-   Simplified `ModuleServiceProvider`: removed router controller factory binding, as this is now handled by Annabel core.
-   AuthMiddleware now receives an explicit access context `'admin'` when applied to the Admin route.

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
