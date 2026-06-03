# Changelog

All notable changes to this project will be documented in this file.

## [1.1.0] - 2025-10-14

### Added

-   Implemented `SupportsInspectionInterface` to allow engine introspection.
-   Added `getLocator()` and `getExtensions()` methods to `PhpEngine`.
-   Improved compatibility with the Annabel framework — `View` can now automatically detect and select engines by template file extension (e.g. `.razor.php`, `.php`).

## [1.0.0] - 2025-10-09

### Added

-   First stable version of the Razor templating engine.
-   Compatibility with the [`codemonster-ru/view`](https://github.com/codemonster-ru/view`) package.
-   Directive support:
-   `{{ $variable }}` — safe variable output.
-   `@if` / `@endif` — conditional blocks.
-   `@foreach` / `@endforeach` — loops.
-   `@include` — inserting other templates.
-   Caching of compiled templates.
-   PHPUnit tests.
-   Full README and documentation.
