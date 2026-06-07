<?php

namespace Codemonster\Support\Tests\Helpers;

use PHPUnit\Framework\TestCase;
use Codemonster\Router\Router;
use Codemonster\Router\Route;

class RouterHelperTest extends TestCase
{
    protected function setUp(): void
    {
        app()->singleton('router', fn() => new Router());
    }

    public function testRouterReturnsInstance()
    {
        $this->assertInstanceOf(Router::class, router());
    }

    public function testRouteRegistration()
    {
        $route = router('/test', fn() => 'ok', 'GET');

        $this->assertInstanceOf(Route::class, $route);
    }
}
