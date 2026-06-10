<?php

namespace Codemonster\Support\Tests\Helpers;

use Codemonster\Router\Route;
use Codemonster\Router\Router;
use PHPUnit\Framework\TestCase;

class RouterHelperTest extends TestCase
{
    protected function setUp(): void
    {
        app()->singleton('router', fn () => new Router());
    }

    public function testRouterReturnsInstance()
    {
        $this->assertInstanceOf(Router::class, router());
    }

    public function testRouteRegistration()
    {
        $route = router('/test', fn () => 'ok', 'GET');

        $this->assertInstanceOf(Route::class, $route);
    }

    public function testRouteGeneratesNamedRouteUri()
    {
        router()->get('/users/{id}', fn () => 'ok')->name('users.show');

        $this->assertSame('/users/42', route('users.show', ['id' => 42]));
    }
}
