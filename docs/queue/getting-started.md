---
title: "Getting started"
description: "First standalone usage of codemonster-ru/queue"
order: 1
---

# Getting started

`codemonster-ru/queue` provides sync, database, and Redis queues, job contracts,
failed job storage, and workers.

## Basic usage

Create a queue connection, dispatch a job, and run a worker to process it.

```php
use Codemonster\Queue\Contracts\JobInterface;
use Codemonster\Queue\SyncQueue;

final class SendWelcomeEmail implements JobInterface
{
    public function handle(): void
    {
        // Send mail.
    }
}

$queue = new SyncQueue();
$queue->push(new SendWelcomeEmail());
```

## Testable timestamps

Database and Redis queues, failed-job retries, and `QueueManager` accept a
PSR-20 clock. Annabel injects its registered application clock automatically.

```php
use Codemonster\DateTime\FrozenClock;
use Codemonster\Queue\DatabaseQueue;

$clock = new FrozenClock(new \DateTimeImmutable('2026-06-09 10:15:00 UTC'));
$queue = new DatabaseQueue($connection, clock: $clock);
```
