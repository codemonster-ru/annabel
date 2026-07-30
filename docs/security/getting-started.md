---
title: "Getting started"
description: "First standalone usage of codemonster-ru/security"
order: 1
---

# Getting started

`codemonster-ru/security` provides CSRF token management, CSRF middleware, rate
limiting, and throttle middleware.

## Basic usage

Use the security helpers to hash credentials and verify submitted values.

```php
use Codemonster\Security\Csrf\CsrfTokenManager;

$csrf = new CsrfTokenManager($session);

$token = $csrf->token();
```

For rate limiting, choose a throttle storage implementation and pass it to
`RateLimiter`.

## Testable rate limits

`RateLimiter`, `ThrottleRequests`, and Redis throttle storage accept a PSR-20
clock. Annabel supplies its registered application clock automatically.

```php
use Codemonster\DateTime\FrozenClock;
use Codemonster\Security\RateLimiting\RateLimiter;

$clock = new FrozenClock(new \DateTimeImmutable('2026-06-09 10:15:00 UTC'));
$limiter = new RateLimiter($storage, clock: $clock);
```
