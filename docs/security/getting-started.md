---
title: "Getting started"
description: "First standalone usage of codemonster-ru/security"
order: 1
---

# Getting started

`codemonster-ru/security` provides CSRF token management, CSRF middleware, rate
limiting, and throttle middleware.

## Basic usage

```php
use Codemonster\Security\Csrf\CsrfTokenManager;

$csrf = new CsrfTokenManager($session);

$token = $csrf->token();
```

For rate limiting, choose a throttle storage implementation and pass it to
`RateLimiter`.
