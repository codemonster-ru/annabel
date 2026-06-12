---
title: "Authorization"
description: "Authorizing actions with gates and middleware"
order: 2
---

# Authorization

Annabel auth includes authorization primitives for checking whether a user may
perform an action.

## Route middleware

Protect a route with an ability check when access depends on the authenticated
user.

```php
router()->get('/posts/{post}', [PostController::class, 'show'])
    ->middleware('can:posts.view,post');
```

## Failures

Authorization failures should return a forbidden response or throw an
authorization exception according to the guard and middleware flow.
