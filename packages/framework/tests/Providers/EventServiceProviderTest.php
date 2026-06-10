<?php

namespace Codemonster\Annabel\Tests\Providers;

use Codemonster\Annabel\Application;
use Codemonster\Annabel\Providers\EventServiceProvider;
use Codemonster\Events\EventDispatcher;
use Codemonster\Events\ListenerProvider;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

class EventServiceProviderTest extends TestCase
{
    protected function tearDown(): void
    {
        Application::resetInstance();
    }

    public function test_event_services_are_registered(): void
    {
        Application::resetInstance();

        $app = new Application(__DIR__ . '/../..', null, false);
        (new EventServiceProvider($app))->register();

        self::assertInstanceOf(ListenerProvider::class, $app->make(ListenerProvider::class));
        self::assertInstanceOf(ListenerProviderInterface::class, $app->make(ListenerProviderInterface::class));
        self::assertInstanceOf(EventDispatcher::class, $app->make(EventDispatcher::class));
        self::assertInstanceOf(EventDispatcherInterface::class, $app->make(EventDispatcherInterface::class));
        self::assertInstanceOf(EventDispatcherInterface::class, $app->make('events'));
    }
}
