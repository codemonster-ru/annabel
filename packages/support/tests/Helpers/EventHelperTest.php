<?php

namespace Codemonster\Support\Tests\Helpers;

use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;

class EventHelperTest extends TestCase
{
    public function testEventDispatchesThroughPsrDispatcher()
    {
        $dispatcher = new FakeEventDispatcher();
        $event = new FakeEvent();

        app()->instance(EventDispatcherInterface::class, $dispatcher);

        $this->assertSame($event, event($event));
        $this->assertSame([$event], $dispatcher->events);
    }
}

class FakeEvent
{
}

class FakeEventDispatcher implements EventDispatcherInterface
{
    public array $events = [];

    public function dispatch(object $event): object
    {
        $this->events[] = $event;

        return $event;
    }
}
