<?php

use Codemonster\Annabel\Http\Kernel;
use Codemonster\Annabel\Http\Request;
use Codemonster\Annabel\Application;
use Codemonster\Router\Router;
use Codemonster\Annabel\Http\Response;
use PHPUnit\Framework\TestCase;

class KernelTest extends TestCase
{
    public function test_kernel_dispatches_route()
    {
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
        $router = new Router();
        $app = new Application(__DIR__ . '/..');
        $kernel = new Kernel($app, $router);

        $res = $kernel->handle(new Request('GET', '/not-found'));

        $this->assertEquals(404, $res->getStatusCode());
    }
}
