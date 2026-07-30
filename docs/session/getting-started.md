---
title: "Getting started"
description: "First standalone usage of codemonster-ru/session"
order: 1
---

# Getting started

`codemonster-ru/session` provides a session store, static session facade,
scoped session data, flash data, TTL values, and file, array, cache, Redis,
Redis Sentinel, and Redis Cluster handlers.

## Basic usage

The following example starts a session and reads and writes session data.

```php
use Codemonster\Session\Session;

Session::start('file', [
    'path' => __DIR__ . '/storage/sessions',
]);

Session::put('user_id', 15);

$userId = Session::get('user_id');
```

Use the array handler for isolated tests.

## Testable expiration

`Session::start()`, `Store`, and the file handler accept a PSR-20 clock.
Annabel injects its registered application clock automatically.

```php
use Codemonster\DateTime\FrozenClock;
use Codemonster\Session\Session;

$clock = new FrozenClock(new \DateTimeImmutable('2026-06-09 10:15:00 UTC'));

Session::start('array', clock: $clock);
Session::putWithTtl('token', 'value', 60);
```
