<?php

namespace Codemonster\Annabel\Events;

use Psr\EventDispatcher\ListenerProviderInterface;

class ListenerProvider implements ListenerProviderInterface
{
    /** @var array<string, list<callable>> */
    protected array $listeners = [];

    public function listen(string $event, callable $listener): void
    {
        $this->listeners[$event][] = $listener;
    }

    /**
     * @return iterable<callable>
     */
    public function getListenersForEvent(object $event): iterable
    {
        foreach ($this->listeners as $eventClass => $listeners) {
            if ($event instanceof $eventClass) {
                foreach ($listeners as $listener) {
                    yield $listener;
                }
            }
        }
    }
}
