---
title: "Task scheduling"
description: "Defining and running scheduled tasks"
order: 8
---

# Task scheduling

Define scheduled tasks in `routes/schedule.php`.

```php
/** @var Codemonster\Scheduler\Schedule $schedule */
$schedule->call(fn () => cleanup(), 'cleanup')
    ->dailyAt('03:00')
    ->withoutOverlapping();
```

Run the scheduler every minute:

```cron
* * * * * cd /path/to/app && php vendor/bin/annabel schedule:run >> /dev/null 2>&1
```

Inspect tasks:

```bash
php vendor/bin/annabel schedule:list
```

Overlap locks use the configured cache store when cache is registered.
