---
title: "Getting started"
description: "First standalone usage of codemonster-ru/scheduler"
order: 1
---

# Getting started

`codemonster-ru/scheduler` defines scheduled callback tasks, cron expressions,
and optional overlap locks.

## Basic usage

Register scheduled callbacks and run the scheduler when tasks become due.

```php
use Codemonster\Scheduler\Schedule;

$schedule = new Schedule();

$schedule->call(fn () => cleanup(), 'cleanup')
    ->dailyAt('03:00')
    ->withoutOverlapping();

$results = $schedule->runDue();
```

Run the scheduler from cron or a process supervisor every minute.

## Testable time

`Schedule` uses a UTC system clock by default and accepts any PSR-20 clock as
its second constructor argument. Annabel injects the clock registered in its
container automatically.

```php
use Codemonster\DateTime\FrozenClock;
use Codemonster\Scheduler\Schedule;

$clock = new FrozenClock(new \DateTimeImmutable('2026-06-09 10:15:00 UTC'));
$schedule = new Schedule(clock: $clock);

$schedule->call(fn () => cleanup())->everyFiveMinutes();

$results = $schedule->runDue();
```

An explicit value passed to `runDue()`, `dueTasks()`, or `isDue()` takes
priority over the configured clock.

The same clock controls expiration in the built-in array overlap-lock store.
