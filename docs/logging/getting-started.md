---
title: "Getting started"
description: "First standalone usage of codemonster-ru/logging"
order: 1
---

# Getting started

`codemonster-ru/logging` provides a PSR-3 file logger and logger manager.

## Basic usage

```php
use Codemonster\Logging\FileLogger;

$logger = new FileLogger(__DIR__ . '/storage/logs/app.log');

$logger->info('User logged in', ['user_id' => 15]);
```

Use `LoggerManager` when you need named logging channels from configuration.
