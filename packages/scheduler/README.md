# Codemonster Scheduler

Task scheduling primitives for Annabel applications.

## Usage

```php
use Codemonster\Scheduler\Schedule;

$schedule = new Schedule();

$schedule->call(fn () => cleanup(), 'cleanup')->dailyAt('03:00');
$schedule->call(fn () => syncFeed(), 'sync-feed')
    ->everyFiveMinutes()
    ->withoutOverlapping();

$results = $schedule->runDue(new DateTimeImmutable());
```

Run `schedule:run` every minute from cron in a framework application.

Use `withoutOverlapping()` for tasks that must not run concurrently. Annabel
uses the configured cache store for scheduler locks when the framework cache
provider is registered.

Framework applications can inspect registered tasks with `schedule:list`.
