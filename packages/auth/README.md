# codemonster-ru/auth

Framework-agnostic authentication primitives for Annabel applications.

## Installation

```bash
composer require codemonster-ru/auth
```

## Concepts

-   `AuthenticatableInterface` describes a user object.
-   `UserProviderInterface` retrieves and validates users.
-   `PasswordHasherInterface` hashes and verifies passwords.
-   `AuthorizerInterface` checks abilities and policies.
-   `SessionGuard` stores the authenticated user id in `codemonster-ru/session`.
-   `Authenticate` middleware rejects guests with `401` or redirects them.
-   `Authorize` middleware rejects requests that fail a gate ability check.

## Example

```php
use Codemonster\Auth\Guards\SessionGuard;
use Codemonster\Auth\Hashing\NativePasswordHasher;
use Codemonster\Auth\Providers\ArrayUserProvider;

$hasher = new NativePasswordHasher();
$provider = new ArrayUserProvider([
    new User(1, 'admin@example.com', $hasher->make('secret')),
], $hasher);

$guard = new SessionGuard($provider, session());

if ($guard->attempt(['email' => 'admin@example.com', 'password' => 'secret'])) {
    echo $guard->id(); // 1
}

$guard->logout(); // Invalidates the session by default.
```

`ArrayUserProvider` is intentionally small. Production applications can provide
their own database-backed implementation of `UserProviderInterface`.

## Authorization

```php
use Codemonster\Auth\Authorization\Gate;

$gate = new Gate($guard);
$gate->define('posts.update', fn($user, $post) => $user?->getAuthIdentifier() === $post->owner_id);

if ($gate->allows('posts.update', $post)) {
    // ...
}
```

In Annabel routes, the `can` middleware alias can read route parameters exposed
by the HTTP kernel:

```php
router()
    ->get('/posts/{post}', [PostController::class, 'show'])
    ->middleware('can:posts.view,post');
```

## Database users

`DatabaseUserProvider` retrieves users through
`codemonster-ru/database`'s `ConnectionInterface`:

```php
use Codemonster\Auth\Database\DatabaseUserProvider;

$provider = new DatabaseUserProvider(
    db(),
    new NativePasswordHasher(),
    table: 'users',
    identifierColumn: 'id',
    passwordColumn: 'password',
    credentialKey: 'email',
);
```

## Annabel integration

`codemonster-ru/annabel` registers the auth services through
`AuthServiceProvider`. Applications can use `auth()` and `user()` helpers, and
protect routes with `Codemonster\Auth\Middleware\Authenticate` and
`Codemonster\Auth\Middleware\Authorize`.
