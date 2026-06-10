<?php

namespace Codemonster\Router\Tests;

use Codemonster\Router\Route;
use Codemonster\Router\Router;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
    public function testGetRouteMatches()
    {
        $router = new Router();
        $route = $router->get('/hello', fn () => 'Hello World');

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

    public function testPostRouteMatches()
    {
        $router = new Router();
        $route = $router->post('/submit', fn () => 'Submitted');

        $matched = $router->dispatch('POST', '/submit');

        $this->assertSame($route, $matched);
    }

    public function testAnyRouteMatchesCommonHttpMethods()
    {
        $router = new Router();
        $route = $router->any('/resource', fn () => 'OK');

        foreach (['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD'] as $method) {
            $this->assertSame($route, $router->dispatch($method, '/resource'));
        }
    }

    public function testTrailingSlashFallbackMatches()
    {
        $router = new Router();
        $route = $router->get('/docs/', fn () => 'Docs');

        $this->assertSame($route, $router->dispatch('GET', '/docs'));
    }

    public function testDuplicateRouteThrows()
    {
        $this->expectException(\RuntimeException::class);

        $router = new Router();
        $router->get('/duplicate', fn () => 'First');
        $router->get('/duplicate', fn () => 'Second');
    }

    public function testDynamicRouteMatchesAndExposesParameters()
    {
        $router = new Router();
        $route = $router->get('/users/{id}', fn () => 'User');

        $matched = $router->dispatch('GET', '/users/42');

        $this->assertSame($route, $matched);
        $this->assertSame(['id' => '42'], $matched?->parameters());
    }

    public function testDynamicRouteHonorsConstraints()
    {
        $router = new Router();
        $router->get('/users/{id}', fn () => 'User')->where('id', '\d+');

        $this->assertInstanceOf(Route::class, $router->dispatch('GET', '/users/42'));
        $this->assertNull($router->dispatch('GET', '/users/admin'));
    }

    public function testNamedRouteGeneratesUri()
    {
        $router = new Router();
        $router->get('/users/{id}', fn () => 'User')->name('users.show');

        $this->assertSame('/users/42?tab=profile', $router->route('users.show', [
            'id' => 42,
            'tab' => 'profile',
        ]));
    }

    public function testNamedRouteRequiresParameters()
    {
        $this->expectException(\InvalidArgumentException::class);

        $router = new Router();
        $router->get('/users/{id}', fn () => 'User')->name('users.show');

        $router->route('users.show');
    }

    public function testUnknownNamedRouteThrows()
    {
        $this->expectException(\RuntimeException::class);

        (new Router())->route('missing');
    }

    public function test_routes_can_be_added_with_multiple_methods_and_inspected(): void
    {
        $router = new Router();
        $route = $router->add(['GET', 'HEAD'], '/health', 'health')
            ->where('id', '\d+');

        $this->assertSame([$route], $router->routes());
        $this->assertSame(['id' => '\d+'], $route->getConstraints());
        $this->assertSame($route, $router->dispatch('HEAD', '/health'));
    }
}
