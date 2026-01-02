<?php

use Codemonster\Annabel\Http\Kernel;
use Codemonster\Http\Request;
use Codemonster\Annabel\Application;
use Codemonster\Router\Router;
use Codemonster\Http\Response;
use PHPUnit\Framework\TestCase;

class TestMiddlewareA
{
    public function handle(Request $req, callable $next, $role = null): mixed
    {
        $out = $next($req);

        return 'A(' . $out . ')';
    }
}

class TestMiddlewareB
{
    public function handle(Request $req, callable $next, $role = null): mixed
    {
        $out = $next($req);

        return 'B(' . $out . ')';
    }
}

class TestMiddlewareRole
{
    public function handle(Request $req, callable $next, $role = null): mixed
    {
        $out = $next($req);

        return $role . ':' . $out;
    }
}

class TestMissingController {}

class KernelTest extends TestCase
{
    public function test_kernel_dispatches_route()
    {
        Application::resetInstance();

        $router = new Router();
        $router->get('/hello', fn() => 'world');
        $app = new Application(__DIR__ . '/..');

        $kernel = new Kernel($app, $router);
        $req = new Request('GET', '/hello');
        $res = $kernel->handle($req);

        $this->assertInstanceOf(Response::class, $res);
        $this->assertEquals('world', $res->getContent());
    }

    public function test_kernel_returns_404()
    {
        Application::resetInstance();

        $router = new Router();
        $app = new Application(__DIR__ . '/..');
        $kernel = new Kernel($app, $router);

        $res = $kernel->handle(new Request('GET', '/not-found'));

        $this->assertEquals(404, $res->getStatusCode());
    }

    public function test_kernel_runs_multiple_middleware_from_single_call()
    {
        Application::resetInstance();

        $router = new Router();
        $router->get('/hello', fn() => 'world')->middleware(TestMiddlewareA::class, TestMiddlewareB::class);
        $app = new Application(__DIR__ . '/..');
        $kernel = new Kernel($app, $router);

        $res = $kernel->handle(new Request('GET', '/hello'));

        $this->assertEquals('A(B(world))', $res->getContent());
    }

    public function test_kernel_treats_class_and_role_as_single_middleware()
    {
        Application::resetInstance();

        $router = new Router();
        $router->get('/hello', fn() => 'world')->middleware([TestMiddlewareRole::class, 'admin']);
        $app = new Application(__DIR__ . '/..');
        $kernel = new Kernel($app, $router);

        $res = $kernel->handle(new Request('GET', '/hello'));

        $this->assertEquals('admin:world', $res->getContent());
    }

    public function test_kernel_handles_missing_controller_method()
    {
        Application::resetInstance();

        $router = new Router();
        $router->get('/missing', [TestMissingController::class, 'missing']);
        $app = new Application(__DIR__ . '/..');
        $kernel = new Kernel($app, $router);

        $res = $kernel->handle(new Request('GET', '/missing'));

        $this->assertEquals(500, $res->getStatusCode());
    }
}
