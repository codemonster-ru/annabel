# Changelog

All significant changes to this project will be documented in this file.

## [2.6.0] - 2026-06-10

### Added

-   Added route inspection APIs used by Annabel route caching and optimization.

### Changed

-   Refined route, route collection, and route group behavior for framework cache/attribute workflows.

## [2.5.0] - 2025-11-16

### Added

-   Duplicate route detection in `RouteCollection::addRoute()`.  
    Now adding two routes with the same path & HTTP method throws
    a clear RuntimeException instead of silently overriding previous definitions.

### Changed

-   Reworked middleware definition mechanism in `Route`:
    now `middleware()` accepts variadic arguments (`...$middleware`)
    and stores middleware exactly as provided.
-   Middleware inheritance in `RouteGroup` has been redesigned.
    Group middleware no longer overrides route middleware and now
    merges correctly in nested groups.

### Fixed

-   Fixed broken middleware arguments when using calls like:
    `->middleware(AuthMiddleware::class, 'admin')`.
-   Correct passing of middleware parameters to Kernel and custom middleware classes.
-   Fixed inconsistent structure of `$route->getMiddleware()` between Route and RouteGroup.

### Improvements

-   Routing pipeline is now fully deterministic.
-   Middleware chains execute in the correct order:
    parent group → child group → route.
-   Foundation for future advanced features (route prefixes, named routes).

## [2.4.0] - 2025-11-16

### Changed

-   Router has been migrated to a "match-only" architecture: controller and middleware execution logic has been completely removed from the package.
-   Route execution logic has been moved to Annabel Kernel (DI, middleware pipeline, controller invocation).
-   Improved RouteGroup structure: correct middleware propagation and inheritance between nested groups has been added.
-   Route group display has been simplified by removing the deprecated group stack.

### Added

-   Added the `RouteCollection::addRoute()` method for registering Route objects without duplication.
-   Added support for inheriting middleware from parent groups within a RouteGroup.

### Fixed

-   Fixed a critical error that prevented middleware from being applied: Router created two different Route objects, and the middleware was assigned to the wrong instance.
-   Fixed the behavior of nested route groups: middleware is now correctly inherited and combined. - Removed unused and misleading controller factory code.

## [2.3.0] – 2025-10-20

### Added

-   **Middleware support** — Routes can now have their own middleware chains using the `Route::middleware()` method.
-   **Route groups** — The `Router::group()` method has been added for organizing routes with a common prefix and middleware.
-   Dispatcher now supports executing middleware as a sequential pipeline before calling the main handler.

### Changed

-   The `Router::get()`, `Router::post()`, and `Router::any()` methods now return `Route` instead of `Router`, allowing for chained calls (`->middleware()`).
-   Improved typing and autocompletion for IDEs (Intelephense, PHPStan).

## [2.2.0] – 2025-10-19

### Added

-   **Automatic route normalization** — Router now treats paths with and without a trailing `/` as the same.

For example, the `/admin` and `/admin/` routes lead to the same handler.

### Changed

-   The `Router::dispatch()` method now normalizes the URI before searching for a route.

This improves UX and eliminates frequent 404 errors due to an extra slash.

## [2.1.0] – 2025-10-19

### Added

-   **Controller Factory support** — Router can now use an external factory to create controllers, allowing it to integrate with DI containers (such as Annabel) without a direct dependency.
-   Router::setControllerFactory() and Router::getControllerFactory() methods.
-   Dispatcher now supports passing a Router instance and calling the factory when creating controllers.

### Changed

-   Dispatcher now accepts a Router in its constructor.
-   Controller creation is now flexible and safe.
-   Full backward compatibility has been preserved—if a factory isn't specified, the controller is created using new.

## [2.0.0] - 2025-09-28

### Changed

-   Raised minimum PHP version to >= 8.2. No public API changes.

## [1.0.0] - 2025-09-26

### Added

-   First release of the `codemonster-ru/router` package.
-   Route support (`get`, `post`, `any`).
-   Handler support: `callable`, `[Controller::class, method]`, `Controller@method`.
-   Minimal `Router`, `Route`, `RouteCollection`, `Dispatcher`.
-   Unit tests (`phpunit`).
-   Documentation (`README.md`).
