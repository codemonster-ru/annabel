<?php

namespace Codemonster\Annabel\Tests;

use Codemonster\Annabel\Application;
use Codemonster\Annabel\Bootstrap\RouteCache;
use Codemonster\Http\Request;
use Codemonster\Router\Router;
use Codemonster\View\View;
use PHPUnit\Framework\TestCase;

class ApplicationTest extends TestCase
{
    public function test_bootstrap_initializes_view()
    {
        Application::resetInstance();

        $app = new Application(__DIR__ . '/..');

        $this->assertInstanceOf(View::class, $app->getView());
    }

    public function test_singleton_is_accessible()
    {
        Application::resetInstance();

        $app = new Application(__DIR__ . '/..');

        $this->assertInstanceOf(Application::class, Application::getInstance());
        $this->assertSame($app, $app->make(Application::class));
    }

    public function test_routes_can_be_registered_before_bootstrap()
    {
        Application::resetInstance();

        $app = new Application(__DIR__ . '/..', null, false);
        $app->get('/hello', fn () => 'world');

        $response = $app->handle(new Request('GET', '/hello'));

        $this->assertSame('world', $response->getContent());
    }

    public function test_cached_routes_can_be_loaded(): void
    {
        Application::resetInstance();

        $basePath = sys_get_temp_dir() . '/annabel-application-' . bin2hex(random_bytes(6));
        mkdir($basePath . '/bootstrap/cache', 0770, true);

        try {
            $router = new Router();
            $router->get('/cached', [CachedRouteController::class, 'show']);
            RouteCache::write($basePath, $router);

            $app = new Application(__DIR__ . '/..');
            $app->loadCachedRoutes(RouteCache::path($basePath));

            $route = $app->getKernel()->getRouter()->dispatch('GET', '/cached');

            $this->assertNotNull($route);
            $this->assertSame([CachedRouteController::class, 'show'], $route->handler);
        } finally {
            @unlink(RouteCache::path($basePath));
            @rmdir($basePath . '/bootstrap/cache');
            @rmdir($basePath . '/bootstrap');
            @rmdir($basePath);
            Application::resetInstance();
        }
    }

    public function test_make_accepts_parameters()
    {
        Application::resetInstance();

        $app = new Application(__DIR__ . '/..');

        $subject = $app->make(ApplicationMakeSubject::class, ['name' => 'annabel']);

        $this->assertSame('annabel', $subject->name);
    }

    public function test_reinitialization_throws()
    {
        Application::resetInstance();

        new Application(__DIR__ . '/..');

        $this->expectException(\RuntimeException::class);

        try {
            new Application(__DIR__ . '/..');
        } finally {
            Application::resetInstance();
        }
    }
}

class ApplicationMakeSubject
{
    public function __construct(public string $name)
    {
    }
}

class CachedRouteController
{
    public function show(): void
    {
    }
}
