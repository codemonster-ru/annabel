---
title: "Configuration files"
description: "Reference for Annabel skeleton configuration files"
order: 3
---

# Configuration files

Annabel applications keep configuration in `config/*.php`. Each file returns an
array and may read environment values through `env()`.

## `config/app.php`

Controls providers, package discovery, attribute routes, and service
autoconfiguration.

Important keys:

- `providers.defaults`: enables default framework providers.
- `providers.disabled`: default providers to disable.
- `providers.extra`: application providers to register.
- `providers.discover`: enables provider discovery from `bootstrap/providers`.
- `providers.path`: provider discovery path.
- `providers.packages.discover`: enables Composer package provider discovery.
- `providers.packages.dont_discover`: package discovery opt-outs.
- `providers.packages.cache`: enables package provider manifest cache.
- `routing.attributes.enabled`: enables controller attribute route discovery.
- `routing.attributes.paths`: paths scanned for route attributes.
- `services.enabled`: enables service attribute discovery.
- `services.paths`: paths scanned for service attributes.

## `config/database.php`

Controls database connections and migration paths.

- `default`: active connection.
- `connections.mysql`: MySQL PDO configuration.
- `connections.sqlite`: SQLite PDO configuration.
- `migrations.table`: migration repository table.
- `migrations.paths`: migration directories.

## `config/session.php`

Controls session driver, cookie options, encryption, and Redis session settings.

Use `array` for tests, `file` for simple local applications, and Redis or
another shared handler for multi-node deployments.

## `config/security.php`

Controls CSRF and throttling.

- `csrf.enabled`: enables CSRF verification.
- `csrf.add_to_kernel`: adds CSRF middleware globally.
- `csrf.verify_json`: verifies JSON requests.
- `csrf.except_methods`: safe methods skipped by CSRF.
- `csrf.except`: URI patterns skipped by CSRF.
- `throttle.enabled`: enables throttling.
- `throttle.add_to_kernel`: adds throttling middleware globally.
- `throttle.storage`: `session`, `database`, or `redis`.
- `throttle.presets`: named presets such as `login` and `api`.
- `throttle.trusted_proxies`: trusted proxy IPs.

## `config/cache.php`

Controls cache stores. Common stores are `array`, `file`, and `redis`.

## `config/queue.php`

Controls queue connections and worker behavior.

- `default`: active queue connection.
- `connections.sync`: inline execution.
- `connections.database`: database-backed queue.
- `connections.redis`: Redis-backed queue.
- `backoff`: retry delay.
- `timeout`: worker timeout.

## `config/mail.php`

Controls mailers and transports.

Supported transports are `array`, `log`, `smtp`, and `sendmail`.

## `config/filesystem.php`

Controls filesystem disks. The current filesystem driver is `local`.

## `config/validation.php`

Controls sensitive fields excluded from flashed old input after validation
failures.
