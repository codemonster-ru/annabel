<?php

namespace Codemonster\Annabel\Tests\Events;

use Codemonster\Annabel\Events\EventDispatcher;
use Codemonster\Annabel\Events\ListenerProvider;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\StoppableEventInterface;

class EventDispatcherTest extends TestCase
{
    public function test_dispatcher_implements_psr_contract_and_calls_listeners()
    {
        $provider = new ListenerProvider();
        $dispatcher = new EventDispatcher($provider);
        $event = new TestEvent();

        $provider->listen(TestEvent::class, function (TestEvent $event): void {
            $event->hits++;
        });

        $this->assertInstanceOf(EventDispatcherInterface::class, $dispatcher);
        $this->assertSame($event, $dispatcher->dispatch($event));
        $this->assertSame(1, $event->hits);
    }

    public function test_dispatcher_stops_for_stoppable_events()
    {
        $provider = new ListenerProvider();
        $dispatcher = new EventDispatcher($provider);
        $event = new TestStoppableEvent();

        $provider->listen(TestStoppableEvent::class, function (TestStoppableEvent $event): void {
            $event->hits++;
            $event->stopped = true;
        });
        $provider->listen(TestStoppableEvent::class, function (TestStoppableEvent $event): void {
            $event->hits++;
        });

        $dispatcher->dispatch($event);

        $this->assertSame(1, $event->hits);
    }
}

class TestEvent
{
    public int $hits = 0;
}

class TestStoppableEvent implements StoppableEventInterface
{
    public int $hits = 0;
    public bool $stopped = false;

    public function isPropagationStopped(): bool
    {
        return $this->stopped;
    }
}
