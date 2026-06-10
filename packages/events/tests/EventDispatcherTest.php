<?php

namespace Codemonster\Events\Tests;

use Codemonster\Events\EventDispatcher;
use Codemonster\Events\ListenerProvider;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\StoppableEventInterface;

class EventDispatcherTest extends TestCase
{
    public function test_dispatcher_implements_psr_contract_and_calls_listeners(): void
    {
        $provider = new ListenerProvider();
        $dispatcher = new EventDispatcher($provider);
        $event = new TestEvent();

        $provider->listen(TestEvent::class, function (TestEvent $event): void {
            $event->hits++;
        });

        self::assertInstanceOf(EventDispatcherInterface::class, $dispatcher);
        self::assertSame($event, $dispatcher->dispatch($event));
        self::assertSame(1, $event->hits);
    }

    public function test_dispatcher_stops_for_stoppable_events(): void
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

        self::assertSame(1, $event->hits);
    }

    public function test_provider_matches_parent_classes_and_interfaces(): void
    {
        $provider = new ListenerProvider();
        $dispatcher = new EventDispatcher($provider);
        $event = new ChildEvent();

        $provider->listen(ParentEvent::class, function (ChildEvent $event): void {
            $event->hits++;
        });
        $provider->listen(TestContract::class, function (ChildEvent $event): void {
            $event->hits++;
        });

        $dispatcher->dispatch($event);

        self::assertSame(2, $event->hits);
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

interface TestContract
{
}

class ParentEvent implements TestContract
{
    public int $hits = 0;
}

class ChildEvent extends ParentEvent
{
}
