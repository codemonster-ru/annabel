---
title: "Logging"
description: "PSR-3 logging and channels"
order: 10
---

# Logging

Annabel binds `Psr\Log\LoggerInterface` in the container.

## Write logs

Write structured messages at a level that reflects their operational severity.

```php
app(Psr\Log\LoggerInterface::class)->info('User registered', [
    'user_id' => 42,
]);
```

You can also resolve the configured logger binding:

```php
app('logger')->warning('Payment provider returned a retryable error.');
```

## Channels

Configure channels in `config/logging.php`. The logging package ships with file
and null channels.

The skeleton writes to `storage/logs/annabel.log` by default.

```php
return [
    'default' => env('LOG_CHANNEL', 'file'),
    'channels' => [
        'file' => [
            'path' => base_path('storage/logs/annabel.log'),
        ],
        'null' => [
            'driver' => 'null',
        ],
    ],
];
```

## Production

Make sure `storage/logs` is writable by the PHP process. In containerized
deployments, forward or collect file logs according to your platform.

Unhandled HTTP exceptions are reported through the configured PSR logger before
the error response is rendered.
