---
title: "Queues"
description: "Dispatching jobs and running workers"
order: 7
---

# Queues

Annabel exposes queues through `queue()` and `dispatch()`.

## Job

Queue jobs encapsulate serializable data and the work performed by a worker.

```php
use Codemonster\Queue\Contracts\JobInterface;

final class SendWelcomeEmailJob implements JobInterface
{
    public function handle(): void
    {
        // ...
    }
}

dispatch(new SendWelcomeEmailJob());
```

## Connections

Supported connections include `sync`, `database`, and `redis`.

Configure queues in `config/queue.php`:

```php
'default' => env('QUEUE_CONNECTION', 'sync'),
'backoff' => (int) env('QUEUE_BACKOFF', 0),
'timeout' => (int) env('QUEUE_TIMEOUT', 0),
```

Database and Redis connections support `retry_after` and `max_attempts`.

## Worker

Start a worker to consume jobs from the configured queue connection.

```bash
php vendor/bin/annabel queue:work
php vendor/bin/annabel queue:failed
php vendor/bin/annabel queue:retry 1
php vendor/bin/annabel queue:flush
```

Run workers under a supervisor in production.

Use `queue:work --once` to process a single job and exit. Use
`queue:work --stop-when-empty` for finite worker runs.

## Failed jobs

Failed jobs can be inspected and retried:

```bash
php vendor/bin/annabel queue:failed
php vendor/bin/annabel queue:retry 1
php vendor/bin/annabel queue:retry all
```

Use `queue:flush` to clear failed jobs after they are no longer needed.
