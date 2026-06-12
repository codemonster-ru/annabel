---
title: "Sessions"
description: "Reading, writing, and flashing session data"
order: 8
---

# Sessions

Annabel exposes sessions through the `session()` helper.

## Store values

```php
session()->put('user_id', 42);

$userId = session()->get('user_id');
```

## Flash data

```php
session()->flash('status', 'Saved.');
```

Validation uses flashed errors and old input for web redirects.

## Drivers

Common session drivers include `array`, `file`, `cache`, Redis, Predis, Redis
Sentinel, and Redis Cluster handlers. Use a shared handler for multi-node
production deployments.

## Configuration

Sessions are configured in `config/session.php`.

```php
return [
    'driver' => env('SESSION_DRIVER', 'file'),
    'path' => base_path('storage/sessions'),
    'cookie' => [
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax',
        'lifetime' => 86400,
        'path' => '/',
    ],
];
```

Use `SESSION_DRIVER=array` in isolated tests and a shared driver such as Redis
for multi-node deployments.

## Cookie security

Set secure cookies in production:

```dotenv
SESSION_COOKIE_SECURE=true
SESSION_COOKIE_SAME_SITE=Lax
SESSION_COOKIE_LIFETIME=86400
```

## Encryption

Session payload encryption is enabled when `SESSION_ENCRYPTION_KEY` is set.

```dotenv
SESSION_ENCRYPTION_KEY=base64-or-raw-secret
SESSION_PREVIOUS_ENCRYPTION_KEY=old-secret-during-rotation
SESSION_ALLOW_PLAINTEXT=true
```

Keep `SESSION_ALLOW_PLAINTEXT=true` during a migration from plaintext sessions,
then disable it after old sessions expire.

## Redis sessions

```dotenv
SESSION_DRIVER=redis
SESSION_REDIS_HOST=127.0.0.1
SESSION_REDIS_PORT=6379
SESSION_REDIS_DATABASE=0
SESSION_REDIS_PREFIX=annabel_session:
SESSION_REDIS_TTL=86400
```
