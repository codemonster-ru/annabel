---
title: "Cache"
description: "Cache stores and PSR-16 usage"
order: 2
---

# Cache

Annabel exposes PSR-16 cache stores through `cache()`.

## Usage

Use the default cache store through the helper for common read and write
operations.

```php
cache()->set('name', 'annabel', 60);

$name = cache()->get('name');
```

The helper also supports concise get/set forms:

```php
$name = cache('name');

cache('name', 'annabel', 60);
```

Use `cache()` with no arguments when you need the underlying PSR-16 store.

```php
$store = cache();

$store->delete('name');
$store->clear();
```

## Stores

Supported stores include:

- `array`: in-memory cache for tests.
- `file`: local filesystem cache.
- `redis`: Redis-backed cache.

Use Redis or another shared store for multi-instance deployments.

## Configuration

Cache stores live in `config/cache.php`.

```php
return [
    'default' => env('CACHE_STORE', 'file'),
    'stores' => [
        'file' => [
            'driver' => 'file',
            'path' => base_path('storage/cache'),
        ],
        'array' => ['driver' => 'array'],
        'redis' => [
            'driver' => 'redis',
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'port' => (int) env('REDIS_PORT', 6379),
            'database' => (int) env('REDIS_CACHE_DB', 0),
            'prefix' => env('CACHE_PREFIX', 'cache:'),
        ],
    ],
];
```

The Redis store uses the PHP Redis extension unless a client object is
configured directly.

## Atomic add

The cache store supports `add()`, which writes only when the key does not
already exist:

```php
$created = cache()->add('lock:report', true, 60);
```

This is useful for simple single-store locks and idempotency guards.
