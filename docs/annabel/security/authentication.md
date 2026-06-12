---
title: "Authentication"
description: "Login, users, guards, and auth middleware"
order: 1
---

# Authentication

Annabel registers authentication services when the auth provider is enabled.

## Configuration

Auth settings live in `config/auth.php`.

Important options:

| Option | Purpose |
| --- | --- |
| `provider` | User provider name, such as `database`, or `null` for custom binding. |
| `credential_key` | Credential field used during `attempt()`, default `email`. |
| `database.table` | User table for the database provider. |
| `database.identifier_column` | User identifier column. |
| `database.password_column` | Password hash column. |
| `session_key` | Session key used to store the authenticated user id. |
| `redirect_to` | Redirect target for unauthenticated web requests. |

## Login

```php
if (auth()->attempt(['email' => $email, 'password' => $password])) {
    return response()->redirect('/dashboard');
}
```

## Current user

```php
$user = user();
```

Check the guard explicitly:

```php
if (auth()->check()) {
    $id = auth()->id();
}
```

## Middleware

```php
router()->get('/dashboard', [DashboardController::class, 'index'])
    ->middleware('auth');
```

Production applications should bind a database-backed user provider.

## Logout

```php
auth()->logout();
```

By default logout invalidates the session.
