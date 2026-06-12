---
title: "Getting started"
description: "First standalone usage of codemonster-ru/events"
order: 1
---

# Getting started

`codemonster-ru/events` provides a PSR-14 listener provider and event
dispatcher.

## Basic usage

Register listeners for an event class, then dispatch event instances through
the dispatcher.

```php
use Codemonster\Events\EventDispatcher;
use Codemonster\Events\ListenerProvider;

$listeners = new ListenerProvider();
$listeners->listen(
    UserRegistered::class,
    function (UserRegistered $event): void {
        // Send a welcome email.
    },
);

$dispatcher = new EventDispatcher($listeners);
$dispatcher->dispatch(new UserRegistered($user));
```
