---
title: "Getting started"
description: "First standalone usage of codemonster-ru/scheduler"
order: 1
---

# Getting started

`codemonster-ru/scheduler` defines scheduled callback tasks, cron expressions,
and optional overlap locks.

## Basic usage

```php
use Codemonster\Scheduler\Schedule;

$schedule = new Schedule();

$schedule->call(fn () => cleanup(), 'cleanup')
    ->dailyAt('03:00')
    ->withoutOverlapping();

$results = $schedule->runDue();
```

Run the scheduler from cron or a process supervisor every minute.
