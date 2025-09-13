<?php

namespace Annabel\Tests;

use Annabel\Routing\Router;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
    public function testCanAddAndMatchRoute(): void
    {
        $router = new Router();
        $router->add('GET', '/test', fn() => 'OK');

        $action = $router->match('GET', '/test');
        $this->assertIsCallable($action);
    }

    public function testReturnsNullForUnknownRoute(): void
    {
        $router = new Router();
        $action = $router->match('GET', '/missing');
        $this->assertNull($action);
    }
}
