---
title: "Getting started"
description: "First standalone usage of codemonster-ru/cache"
order: 1
---

# Getting started

`codemonster-ru/cache` provides PSR-16 compatible cache stores for array, file,
and Redis-backed cache.

## Basic usage

Create a cache store and use it to persist values for a bounded lifetime.

```php
use Codemonster\Cache\FileCache;

$cache = new FileCache(__DIR__ . '/storage/cache');

$cache->set('users.count', 15, 60);

$count = $cache->get('users.count', 0);
```

Use `ArrayCache` for tests or per-request memory cache. Use `CacheManager` when
you need named stores from configuration.

## Testable expiration

All stores and `CacheManager` accept a PSR-20 clock. Annabel injects its
registered application clock automatically.

```php
use Codemonster\Cache\ArrayCache;
use Codemonster\DateTime\FrozenClock;

$clock = new FrozenClock(new \DateTimeImmutable('2026-06-09 10:15:00 UTC'));
$cache = new ArrayCache($clock);

$cache->set('token', 'value', 60);
```
