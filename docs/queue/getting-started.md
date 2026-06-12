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
