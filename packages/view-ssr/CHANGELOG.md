# Changelog

All notable changes to this project are documented in this file.

## [2.0.0] - 2025-09-28

### Changed

-   Updated dependencies:
-   `codemonster-ru/view` → **^2.0** (previously ^1.1)
-   `codemonster-ru/ssr-bridge` → **^2.0** (previously ^1.1)

## [1.0.0] - 2025-09-28

### Added

-   First release of the SSR engine: `Codemonster\View\Engines\SsrEngine`, implements `EngineInterface`.
-   Render delegation to the bridge with the `render(string $view, array $data = []): string` method.
-   Soft coupling to the official bridge: if the `Codemonster\Ssr\SsrBridge` class is available, the constructor requires its instance (provides early diagnostics).
-   Tests (`tests/SsrEngineTest.php`): success, no `render` method, non-string response.
-   README with installation and usage example.
-   `composer.json`: PHP `>=8.2`, dependency on `codemonster-ru/view` `^1.1` (locator in core), autoloader optimization, package sorting, archive exclusions.
-   `phpunit.xml.dist` and `composer test` script.
