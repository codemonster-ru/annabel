---
title: "Build a login flow"
description: "Create a session-based login flow"
order: 2
---

# Build a login flow

This recipe uses Annabel auth, sessions, validation, CSRF protection, and route
middleware.

## Configure auth

For a database-backed provider:

```dotenv
AUTH_PROVIDER=database
AUTH_TABLE=users
AUTH_CREDENTIAL_KEY=email
AUTH_PASSWORD_COLUMN=password
```

The `users` table must contain the credential column and password hash column.

## Routes

```php
use App\Controllers\AuthController;
use App\Controllers\DashboardController;

$app->get('/login', [AuthController::class, 'create']);
$app->post('/login', [AuthController::class, 'store']);
$app->post('/logout', [AuthController::class, 'destroy']);

router()->get('/dashboard', [DashboardController::class, 'index'])
    ->middleware('auth');
```

## Controller

```php
namespace App\Controllers;

use Codemonster\Annabel\Http\ValidatesRequests;
use Codemonster\Http\Request;

final class AuthController
{
    use ValidatesRequests;

    public function create(): mixed
    {
        return view('auth/login');
    }

    public function store(Request $request): mixed
    {
        $credentials = $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (!auth()->attempt($credentials)) {
            return response()->redirect('/login');
        }

        return response()->redirect('/dashboard');
    }

    public function destroy(): mixed
    {
        auth()->logout();

        return response()->redirect('/login');
    }
}
```

## Form

```php
<form method="post" action="/login">
    <?= csrf_field() ?>

    <input name="email" value="<?= htmlspecialchars((string) old('email'), ENT_QUOTES, 'UTF-8') ?>">
    <input name="password" type="password">

    <button type="submit">Sign in</button>
</form>
```

Validation excludes sensitive fields such as `password` from flashed old input
according to `config/validation.php`.
