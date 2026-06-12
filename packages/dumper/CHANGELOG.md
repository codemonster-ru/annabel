# Changelog

All notable changes to this project will be documented in this file.

## [1.0.0] - 2025-10-07

### Added

- Initial release of `codemonster-ru/dumper`
- Added `Dumper::dump()` with optional `$mode` argument (`cli` | `html`)
- Added `Dumper::dd()` for dump-and-die behavior
- Supports CLI color output and HTML formatted output
- Added `terminate()` method for test-safe exit handling
- Added PHPUnit test suite for CLI, HTML, and `dd()` behavior
