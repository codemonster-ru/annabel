# Changelog

All notable changes to this project will be documented in this file.

## [2.1.0] - 2026-06-10

### Added

- Added `Config::loadCached()` for framework configuration cache files.

### Changed

- Tightened config loading validation for cached and file-based configuration.

## [2.0.1] - 2025-10-05

### Fixed

- Removed global `config()` helper from the package to keep framework responsibility in `codemonster-ru/annabel`.
- Removed unused `autoload-dev` section from `composer.json`.
- Minor internal cleanup and metadata verification.

### Docs

- Updated `README.md` to reflect removal of the global helper.
    Usage examples now use static `Codemonster\Config\Config` API.

### Tests

- Removed test cases for deprecated global helper.
    Tests now cover only `Config::get()`, `Config::set()`, and `Config::all()`.

## [2.0.0] - 2025-09-28

### Changed

- Raised minimum PHP version to >= 8.2. No public API changes.

## [1.0.1] - 2025-09-26

### Changed

- Removed references to .env, as it may not exist.

## [1.0.0] - 2025-09-26

### Added

- Initial release of **codemonster-ru/config**.
- Provides configuration repository for PHP applications.
- Includes:
    - `Config::load()` to load `config/*.php` files
    - `config('key', $default)` helper to get values
    - `config(['key' => value])` to set values dynamically
