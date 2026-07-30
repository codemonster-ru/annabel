---
title: "Getting started"
description: "First standalone usage of codemonster-ru/logging"
order: 1
---

# Getting started

`codemonster-ru/logging` provides a PSR-3 file logger and logger manager.

## Basic usage

Create a logger with one or more handlers, then write messages at the
appropriate level.

```php
use Codemonster\Logging\FileLogger;

$logger = new FileLogger(__DIR__ . '/storage/logs/app.log');

$logger->info('User logged in', ['user_id' => 15]);
```

Use `LoggerManager` when you need named logging channels from configuration.

## Testable timestamps

Both `FileLogger` and `LoggerManager` accept an optional PSR-20 clock. Annabel
passes its application clock automatically; standalone applications can inject
a frozen clock when deterministic log timestamps are required.

```php
use Codemonster\DateTime\FrozenClock;
use Codemonster\Logging\FileLogger;

$clock = new FrozenClock(new \DateTimeImmutable('2026-07-31 12:30:00 UTC'));
$logger = new FileLogger(__DIR__ . '/storage/logs/app.log', $clock);
```
