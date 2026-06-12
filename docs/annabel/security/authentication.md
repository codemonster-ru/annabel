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
| `provider` | Provider name, or `null` for a custom binding. |
| `credential_key` | Credential field for `attempt()`; defaults to `email`. |
| `database.table` | User table for the database provider. |
| `database.identifier_column` | User identifier column. |
| `database.password_column` | Password hash column. |
| `session_key` | Session key used to store the authenticated user id. |
| `redirect_to` | Redirect target for unauthenticated web requests. |

## Login

Attempt authentication with the credential fields configured for the active
provider.

```php
if (auth()->attempt(['email' => $email, 'password' => $password])) {
    return response()->redirect('/dashboard');
}
```

## Current user

Read the authenticated user through the auth service or convenience helper.

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

Apply auth middleware to routes that require an authenticated session.

```php
router()->get('/dashboard', [DashboardController::class, 'index'])
    ->middleware('auth');
```

Production applications should bind a database-backed user provider.

## Logout

End the authenticated session when the user signs out.

```php
auth()->logout();
```

By default logout invalidates the session.
