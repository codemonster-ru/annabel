---
title: "Schedule cleanup work"
description: "Run recurring cleanup tasks"
order: 5
---

# Schedule cleanup work

Scheduled tasks live in `routes/schedule.php`.

## Define a task

```php
/** @var Codemonster\Scheduler\Schedule $schedule */
$schedule->call(function (): void {
    storage('local')->deleteDirectory('tmp/old-exports');
}, 'cleanup-old-exports')->dailyAt('03:00')->withoutOverlapping();
```

## Run scheduler

Run the scheduler every minute from cron:

```cron
* * * * * cd /path/to/app && php vendor/bin/annabel schedule:run >> /dev/null 2>&1
```

## Inspect tasks

```bash
php vendor/bin/annabel schedule:list
```

Overlap locks use the configured cache store when cache is registered. Use a
shared cache store for multi-instance deployments.
