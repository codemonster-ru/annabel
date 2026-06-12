---
title: "Rate limiting"
description: "Throttling requests and protecting endpoints"
order: 4
---

# Rate limiting

Annabel rate limiting is provided by the security integration.

## Throttle middleware

Attach a named throttling preset to routes that need request limits.

```php
router()->post('/login', [LoginController::class, 'store'])
    ->middleware('throttle:login');
```

## Storage

Rate limiting can use session, database, or Redis-backed storage.

Configure presets and storage in `config/security.php`.

```php
'throttle' => [
    'enabled' => true,
    'add_to_kernel' => true,
    'max_attempts' => 60,
    'decay_seconds' => 60,
    'storage' => 'session',
    'presets' => [
        'login' => [
            'ip' => [
                'max_attempts' => 60,
                'decay_seconds' => 60,
            ],
            'account' => [
                'max_attempts' => 5,
                'decay_seconds' => 300,
                'field' => 'email',
            ],
        ],
    ],
],
```

## Trusted proxies

When the application runs behind a proxy, configure trusted proxy IPs so
throttling can use the correct client address:

```dotenv
SECURITY_TRUSTED_PROXIES=10.0.0.10,10.0.0.11
```
