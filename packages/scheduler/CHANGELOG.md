# Changelog

All notable changes to `codemonster-ru/scheduler` will be documented in this file.

## [1.1.0] - 2026-11-27

- Added optional PSR-20 clock injection for due-task evaluation.
- Applied the configured clock to in-memory overlap-lock expiration.

## [1.0.0] - 2026-06-10

- Initial schedule, scheduled task, and result primitives.
- Added scheduler locks and `withoutOverlapping()` task protection.
- Added read-only task inspection helpers for diagnostics.
