---
title: "Environment variables"
description: "Environment variables used by the Annabel skeleton"
order: 2
---

# Environment variables

The skeleton reads environment values from `.env` and the process environment.
Use `.env.example` as the local template and keep real production values in the
deployment environment.

## Application

These variables define the application identity, environment, and debug
behavior.

| Variable | Default | Purpose |
| --- | --- | --- |
| `APP_ENV` | `local` | Application environment name. |
| `APP_DEBUG` | `true` | Enables debug output. Set `false` in production. |

## Logging

Logging variables select the default channel and minimum recorded level.

| Variable | Default | Purpose |
| --- | --- | --- |
| `LOG_CHANNEL` | `file` | Default log channel. |
| `LOG_FILE` | empty | Optional log file override. |

## Database

Database variables configure the default connection used by the application.

| Variable | Default | Purpose |
| --- | --- | --- |
| `DB_CONNECTION` | `mysql` | Default connection name. |
| `DB_HOST` | `127.0.0.1` | MySQL host. |
| `DB_PORT` | `3306` | MySQL port. |
| `DB_DATABASE` | `annabel` | Database name or SQLite path. |
| `DB_USERNAME` | `root` | Database username. |
| `DB_PASSWORD` | empty | Database password. |

## Cache and Redis

These variables configure cache storage and the Redis connection shared by
Redis-backed services.

| Variable | Default | Purpose |
| --- | --- | --- |
| `CACHE_STORE` | `file` | Default cache store. |
| `CACHE_PATH` | empty | File cache path override. |
| `CACHE_PREFIX` | `cache:` | Cache key prefix. |
| `REDIS_HOST` | `127.0.0.1` | Redis host. |
| `REDIS_PORT` | `6379` | Redis port. |
| `REDIS_PASSWORD` | empty | Redis password. |
| `REDIS_CACHE_DB` | `0` | Redis database for cache. |
| `REDIS_QUEUE_DB` | `0` | Redis database for queues. |
| `REDIS_TIMEOUT` | `2.0` | Redis timeout. |

## Sessions

Session variables control the storage driver, cookie behavior, and session
lifetime.

| Variable | Default | Purpose |
| --- | --- | --- |
| `SESSION_DRIVER` | `file` | Session driver. |
| `SESSION_PATH` | empty | File session path override. |
| `SESSION_COOKIE_SECURE` | `false` | Send session cookies over HTTPS only. |
| `SESSION_COOKIE_SAME_SITE` | `Lax` | SameSite cookie attribute. |
| `SESSION_COOKIE_LIFETIME` | `86400` | Cookie lifetime in seconds. |
| `SESSION_ENCRYPTION_KEY` | empty | Enables encrypted session payloads. |
| `SESSION_PREVIOUS_ENCRYPTION_KEY` | empty | Previous key for rotation. |
| `SESSION_ALLOW_PLAINTEXT` | `true` | Read old plaintext sessions. |
| `SESSION_REDIS_HOST` | `127.0.0.1` | Redis session host. |
| `SESSION_REDIS_PORT` | `6379` | Redis session port. |
| `SESSION_REDIS_DATABASE` | `0` | Redis session database. |
| `SESSION_REDIS_PREFIX` | `annabel_session:` | Redis session key prefix. |
| `SESSION_REDIS_TTL` | `86400` | Redis session TTL. |

## Queue

Queue variables select the backend and worker defaults.

| Variable | Default | Purpose |
| --- | --- | --- |
| `QUEUE_CONNECTION` | `sync` | Default queue connection. |
| `QUEUE_REDIS_PREFIX` | `queue:` | Redis queue key prefix. |
| `QUEUE_TABLE` | `jobs` | Database queue table. |
| `QUEUE_FAILED_TABLE` | `failed_jobs` | Failed jobs table. |
| `QUEUE_RETRY_AFTER` | `60` | Seconds before reserved jobs are retried. |
| `QUEUE_MAX_ATTEMPTS` | `3` | Maximum attempts before failure. |
| `QUEUE_BACKOFF` | `0` | Seconds to wait before retry. |
| `QUEUE_TIMEOUT` | `0` | Worker timeout. |

## Mail

Mail variables configure the default transport and sender identity.

| Variable | Default | Purpose |
| --- | --- | --- |
| `MAIL_MAILER` | `log` | Default mailer. |
| `MAILER_DSN` | `smtp://localhost:25` | SMTP DSN. |
| `MAIL_FROM_ADDRESS` | `hello@example.com` | Default sender address. |
| `MAIL_FROM_NAME` | `Annabel` | Default sender name. |
| `MAIL_SENDMAIL_COMMAND` | `/usr/sbin/sendmail -t -i` | Sendmail command. |

## HTTP client

HTTP client variables define request defaults such as timeouts and redirects.

| Variable | Default | Purpose |
| --- | --- | --- |
| `HTTP_CLIENT_BASE_URL` | empty | Optional default base URL. |
| `HTTP_CLIENT_TIMEOUT` | `30` | Request timeout. |

## Security

Security variables provide application secrets and security-related defaults.

| Variable | Default | Purpose |
| --- | --- | --- |
| `SECURITY_CSRF_ENABLED` | `true` | Enables CSRF verification. |
| `SECURITY_CSRF_ADD_TO_KERNEL` | `true` | Adds CSRF middleware globally. |
| `SECURITY_CSRF_VERIFY_JSON` | `false` | Verifies JSON requests. |
| `SECURITY_CSRF_INPUT_KEY` | `_token` | CSRF form input key. |
| `SECURITY_THROTTLE_ENABLED` | `true` | Enables throttling. |
| `SECURITY_THROTTLE_ADD_TO_KERNEL` | `true` | Add global throttling. |
| `SECURITY_THROTTLE_MAX_ATTEMPTS` | `60` | Default max attempts. |
| `SECURITY_THROTTLE_DECAY_SECONDS` | `60` | Default decay window. |
| `SECURITY_THROTTLE_STORAGE` | `session` | Throttle storage driver. |
| `SECURITY_THROTTLE_TABLE` | `throttle_requests` | Database throttle table. |
| `SECURITY_THROTTLE_PREFIX` | `throttle:` | Throttle key prefix. |
| `SECURITY_TRUSTED_PROXIES` | empty | Comma-separated trusted proxy IPs. |
