<?php

namespace Codemonster\Annabel\Providers;

use Codemonster\Annabel\Container;
use Codemonster\Events\EventDispatcher;
use Codemonster\Events\ListenerProvider;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

class EventServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app()->singleton(ListenerProvider::class, fn (): ListenerProvider => new ListenerProvider());
        $this->app()->singleton(ListenerProviderInterface::class, fn (Container $app): ListenerProviderInterface => $app->make(ListenerProvider::class));
        $this->app()->singleton(EventDispatcher::class, fn (Container $app): EventDispatcher => new EventDispatcher($app->make(ListenerProvider::class)));
        $this->app()->singleton(EventDispatcherInterface::class, fn (Container $app): EventDispatcherInterface => $app->make(EventDispatcher::class));
        $this->app()->singleton('events', fn (Container $app): EventDispatcherInterface => $app->make(EventDispatcherInterface::class));
    }
}
