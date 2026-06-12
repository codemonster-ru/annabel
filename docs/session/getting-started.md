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
