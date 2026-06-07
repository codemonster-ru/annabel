<?php

namespace Codemonster\Annabel\Events;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\StoppableEventInterface;

class EventDispatcher implements EventDispatcherInterface
{
    public function __construct(protected ListenerProvider $listeners) {}

    public function dispatch(object $event): object
    {
        foreach ($this->listeners->getListenersForEvent($event) as $listener) {
            if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
                break;
            }

            $listener($event);
        }

        return $event;
    }

    public function listen(string $event, callable $listener): void
    {
        $this->listeners->listen($event, $listener);
    }
}
