---
title: "Events"
description: "PSR-14 event dispatching"
order: 3
---

# Events

Annabel binds PSR-14 event dispatcher services.

## Dispatch events

Register listeners by event class, then dispatch event objects through the
shared dispatcher.

```php
event(new UserRegistered($user));
```

`event()` returns the dispatched event object.

## Register listeners

Register listeners through the listener provider or dispatcher binding:

```php
use Codemonster\Events\EventDispatcher;

app(EventDispatcher::class)->listen(
    UserRegistered::class,
    function (UserRegistered $event): void {
        // Send mail, write an audit log, or dispatch a job.
    },
);
```

Listeners can also be registered during service provider boot.

## Listener provider

Listeners registered for a parent class or interface receive matching events.
Stoppable events follow PSR-14 propagation rules.

## Stoppable events

Use a stoppable event when one listener must be able to prevent later listeners
from running.

```php
use Psr\EventDispatcher\StoppableEventInterface;

final class ImportStarted implements StoppableEventInterface
{
    private bool $stopped = false;

    public function stop(): void
    {
        $this->stopped = true;
    }

    public function isPropagationStopped(): bool
    {
        return $this->stopped;
    }
}
```

When `isPropagationStopped()` returns `true`, remaining listeners are skipped.
