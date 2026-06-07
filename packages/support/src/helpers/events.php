<?php

use Psr\EventDispatcher\EventDispatcherInterface;

if (!function_exists('event')) {
    function event(object $event): object
    {
        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = app(EventDispatcherInterface::class);

        return $dispatcher->dispatch($event);
    }
}
