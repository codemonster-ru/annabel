# Changelog

All notable changes to this project will be documented in this file.

## [2.1.0] - 2026-08-12

### Added

- Added registered, prefix-owned self-closing and paired components.
- Added static, expression, and boolean component props.
- Added lazy default and named slots with nested component support.
- Added component providers, resolver contracts, and registration-aware template caching.
- Added the explicit `RenderedHtml` trust boundary for component and slot composition.
- Added template-aware diagnostics for unknown and malformed components.

### Security

- Kept ordinary props and interpolated slot values escaped while allowing only explicit
  `RenderedHtml` values to compose without double escaping.

## [2.0.0] - 2026-08-10

### Added

- Added explicit raw output with `{!! ... !!}` while keeping `{{ ... }}` escaped.
- Added `@elseif`, `@else`, `@for`, `@while`, `@switch`, `@break`, and `@continue` directives.
- Added nested layouts and sections through `@extends`, `@section`, `@endsection`, and `@yield`.
- Added explicit include data that overrides inherited template data.
- Added Razor comments, escaped at signs, and literal client-side double braces.
- Added template-aware syntax errors and strict handling of unsupported output values.

### Changed

- Replaced regular-expression directive compilation with a balanced expression parser.
- Made compiled-template publication atomic and cache invalidation content-based.
- Restored output buffers reliably when rendering fails.

### Removed

- Removed implicit exposure of all runtime variables to included templates.

## [1.1.0] - 2025-10-14

### Added

- Implemented `SupportsInspectionInterface` to allow engine introspection.
- Added `getLocator()` and `getExtensions()` methods to `PhpEngine`.
- Improved compatibility with the Annabel framework — `View` can now automatically detect and select engines by template file extension (e.g. `.razor.php`, `.php`).

## [1.0.0] - 2025-10-09

### Added

- First stable version of the Razor templating engine.
- Compatibility with the [`codemonster-ru/view`](https://github.com/codemonster-ru/view`) package.
- Directive support:
- `{{ $variable }}` — safe variable output.
- `@if` / `@endif` — conditional blocks.
- `@foreach` / `@endforeach` — loops.
- `@include` — inserting other templates.
- Caching of compiled templates.
- PHPUnit tests.
- Full README and documentation.
