# Changelog

All notable changes to this package are documented in this file.

## [1.2.0] - 2026-01-02

### Changed

-   Debug mode now rethrows view-renderer errors instead of swallowing them.
-   Plain-text fallback no longer exposes exception details.
-   HTTP status codes are normalized to the 100-599 range.
-   View renderer callables are normalized to closures.
-   Template base path is configurable via constructor.
-   Status template file tests now use a temp directory.
-   README updated with behavior notes and template path example.
-   PHPUnit configuration migrated; .phpunit.cache ignored.
-   Updated dependency `codemonster-ru/http` to ^2.0.

## [1.1.0] - 2025-11-16

### Changed

-   Improved the generic error template: now only the standard, safe error message corresponding to the HTTP code is displayed.
-   Removed the display of raw exception messages in page headers (title and h2) to improve security.
-   Consistent message generation style: strict matching of the HTTP code with predefined text is used.
-   Updated the footer in the template (Codemonster Errors).

## [1.0.0] - 2025-11-10

### Added

-   Base interface `ExceptionHandlerInterface`.
-   `SmartExceptionHandler` class with support for:
-   Automatic HTTP status detection (404, 500, 401, etc.).
-   Debug mode (`debug.php`) and production mode (`generic.php`).
-   Fallback logic for template errors.
-   Works without dependence on `View` (via custom `callable $viewRenderer`).
-   Base HTML templates:
-   `resources/views/errors/generic.php`.
-   `resources/views/errors/debug.php`.
-   Full unit test coverage (PHPUnit 9.6-12.0).
