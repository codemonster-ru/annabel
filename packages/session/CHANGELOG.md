# Changelog

All significant changes to this project will be documented in this file.

## [2.0.0] - 2025-12-28

### Added

- Payload encryption (libsodium), key rotation, and plaintext auto-migration.
- TTL support: putWithTtl, ttl, expiresAt, touch, touchAll, sweepExpired.
- Namespaced sessions, SessionScope, and SessionManager (non-static API).
- New handlers: Redis, Redis Cluster, Redis Sentinel, Predis, PSR-16 Cache.
- Retry/backoff for Redis/Predis/Cache handlers.
- Helpers: keysMatch, dump redaction, size, count, and debug tools.
- CI: PHP matrix on Linux/Windows/macOS, static analysis, coverage, Redis integration, stress tests.
- Docs: security policy, checklist, migration guide, production configs.

### Fixed

- Session ID validation and fixation protection.
- Session destroy no longer starts a new session and clears cookie by default.
- File handler locking and mkdir failure handling.
- JSON encode/decode errors now surface as exceptions.

### Changed

- ArraySessionHandler now stores raw payload strings.
- Cookie defaults auto-detect HTTPS and enforce secure for SameSite=None.

## [1.1.0] - 2025-11-16

### Added

- Implemented support for user session ID via the SESSION_ID cookie.
- Added automatic setting of the cookie at session start.
- The session system now stores the ID between requests.

### Fixed

- Fixed a critical bug where each session was re-created on each request.
- Fixed desynchronized behavior between Session::start() and session().
- Fixed incorrect Store behavior where session data was never saved or re-read.

### Changed

- Updated Store::__construct() logic - the session ID is now read from the cookie.
- Store::start() logic now sets the session cookie on first run.
- Session::store() no longer creates a new Store each time and works reliably.

## [1.0.0] - 2025-10-24

### Added

- Session facade - simple static API to manage sessions in any PHP project:
  - Session::start() - initializes a session with file or array handler.
  - Session::get(), Session::put(), Session::forget(), Session::all(), Session::destroy().
- Store class - encapsulates individual session state and serialization logic (JSON-based).
- Session handlers:
  - FileSessionHandler - stores session data as JSON files.
  - ArraySessionHandler - in-memory session handler, useful for CLI or testing.
  - Full compatibility with native PHP SessionHandlerInterface.
- Custom handler support - pass any handler implementing SessionHandlerInterface.