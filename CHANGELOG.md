# Changelog

## [0.1.0] – 2025-10-18

### Added

-   🧘 **Xen Core Module** — a basic system module that automatically initializes all other CMS modules.
-   ⚙️ **ModuleManager** — dynamically finds and loads all `ModuleServiceProvider` in `app/Modules/*`.
-   🪶 **Automatic Bootstrapping** — `bootstrap/app.php` is now minimal, and the Xen core is automatically included.
-   🧩 **View namespaces** — added support for `$view->addNamespace()` for templates within modules.
-   💎 **Pages module example** — added a basic `Pages` module with the `/` route and the `pages::home` template.

### Changed

-   Simplified startup: the CMS now fully loads with `composer start`, eliminating the need to manually register modules.
-   The Annabel framework is integrated with Xen via the `Core` system module.

### Fixed

-   Fixed issues with 404 errors on startup and incorrect module loading.
-   Fixed `Undefined array key` errors when searching for `namespace` in `ModuleManager`.
