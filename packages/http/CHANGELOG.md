# Changelog

All notable changes to this project will be documented in this file.

## [2.1.0] - 2026-06-10

### Added

- `Request` now implements `Psr\Http\Message\ServerRequestInterface`.
- `Response` now implements `Psr\Http\Message\ResponseInterface`.
- Added lightweight `Stream` and `Uri` PSR-7 value objects.

### Changed

- Advanced the development branch alias to `2.1.x-dev`.

## [2.0.0] - 2025-10-24

### Added

- `Request`: `files()`, `headers()`, `server()`, `fullUrlWithQuery()`, `only()`, `except()`, `withHeader()`, `withoutHeader()`, `withQuery()`, `withInput()`.
- `Request`: `userAgent()`, `ip()`, `isSecure()`, `setTrustedProxies()` and `getTrustedProxies()` (IPv4/IPv6 CIDR).
- `Response`: `sendFor()`, `sendHead()`, `withHeader()`, `withoutHeader()`, `withStatus()`, `withoutHeaders()`.
- `Response`: `withHeaders()` is now immutable.
- `Response`: `withCookie()`, `withoutCookie()`, `cookie()` with Set-Cookie support.
- `Response::header()` accepts arrays for multi-value headers (e.g. `Set-Cookie`).
- Cookies: `SameSite=None` automatically adds `Secure`.
- `Response`: `withType()` for immutable content type changes.
- `Response::json()` supports custom `json_encode` flags.

### Changed

Breaking changes since 1.0.0:

- `Request::all()` now returns merged query + body + files (previously returned meta array).
- `Request::ip()` and `Request::isSecure()` now respect `Request::setTrustedProxies()`.
- `Response::withHeaders()` is now immutable (returns a new instance).
- `Response::header()` and `Response::withHeader()` accept arrays for multi-value headers.
- `Request::all()` now returns merged query + body + files.
- `Request::capture()` supports method override via `X-HTTP-Method-Override` and `_method`.
- `Request` parses `application/x-www-form-urlencoded` from raw body when `$_POST` is empty.
- `Request` normalizes uploaded files (including nested structures).
- `Request::only()` and `Request::except()` support dot-notation for nested data.
- `Request::input()` and `Request::query()` support dot-notation.
- `Request::files()` supports dot-notation.
- `Response::send()` skips body for 204/304 and throws when headers are already sent.

### Fixed

- README: corrected section headings and updated usage examples.

## [1.0.0] - 2025-10-23

### Added

- **`Request`** - lightweight immutable HTTP request object with:

    - method, URI, query, body, headers, and raw body accessors;
    - JSON body parsing with `application/json` detection;
    - full URL composition (`scheme()`, `host()`, `fullUrl()`);
    - static factory `Request::capture()` to build from PHP globals.

- **`Response`** - HTTP response class with:
    - content, status, and headers management;
    - helper methods: `json()`, `redirect()`, `type()`, and `empty()`;
    - CLI-safe output via `send()`;
    - string casting via `__toString()`.
