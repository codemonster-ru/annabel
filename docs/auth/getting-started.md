---
title: "Getting started"
description: "First standalone usage of codemonster-ru/auth"
order: 1
---

# Getting started

`codemonster-ru/auth` provides guards, user providers, password hashing,
authentication middleware, and authorization gates.

## Basic usage

Standalone applications wire the package by composing a user provider, session
store, password hasher, and guard.

```php
use Codemonster\Auth\Authorization\Gate;

$gate = new Gate($guard);

$gate->define('posts.update', function ($user, $post): bool {
    return $post->user_id === $user->getAuthIdentifier();
});

$gate->authorize('posts.update', $post);
```
