<?php

namespace Codemonster\Router\Tests;

use Codemonster\Router\Router;
use Codemonster\Router\Route;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
    public function testGetRouteMatches()
    {
        $router = new Router();
        $route = $router->get('/hello', fn() => 'Hello World');

        $matched = $router->dispatch('GET', '/hello');

        $this->assertInstanceOf(Route::class, $matched);
        $this->assertSame($route, $matched);

        $handler = $matched->handler;
        $this->assertEquals('Hello World', $handler());
    }

    public function testRouteNotFound()
    {
        $router = new Router();
        $result = $router->dispatch('GET', '/missing');

        $this->assertNull($result);
    }
}
