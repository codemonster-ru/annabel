# Codemonster Events

PSR-14 event dispatcher primitives for Annabel applications.

## Usage

```php
use Codemonster\Events\EventDispatcher;
use Codemonster\Events\ListenerProvider;

$listeners = new ListenerProvider();
$dispatcher = new EventDispatcher($listeners);

$listeners->listen(UserRegistered::class, function (UserRegistered $event): void {
    // Send a welcome email, write an audit log, or trigger domain work.
});

$dispatcher->dispatch(new UserRegistered());
```

Listeners registered for a parent class or interface also receive matching
events. Stoppable events implementing `Psr\EventDispatcher\StoppableEventInterface`
stop propagation according to PSR-14.
