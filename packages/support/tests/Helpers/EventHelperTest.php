<?php

declare(strict_types=1);

namespace Codemonster\Support\Tests\Helpers;

use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;

class EventHelperTest extends TestCase
{
    public function testEventDispatchesThroughPsrDispatcher(): void
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
    /** @var list<object> */
    public array $events = [];

    public function dispatch(object $event): object
    {
        $this->events[] = $event;

        return $event;
    }
}
