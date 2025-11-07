<?php

namespace Codemonster\Annabel\Http;

use Codemonster\Annabel\Application;
use Codemonster\Annabel\Contracts\ExceptionHandlerInterface;
use Codemonster\Router\Router;
use Codemonster\Http\Request;
use Codemonster\Http\Response;
use Throwable;

class Kernel
{
    protected Application $app;
    protected Router $router;
    protected array $middleware = [];

    public function __construct(Application $app, Router $router)
    {
        $this->app = $app;
        $this->router = $router;
    }

    public function handle(Request $request): Response
    {
        try {
            $response = $this->runMiddleware($request, fn($req) => $this->dispatch($req));

            if (!$response instanceof Response) {
                $response = new Response((string) $response);
            }

            return $response;
        } catch (Throwable $e) {
            $handler = $this->app->make(ExceptionHandlerInterface::class);

            return $handler->handle($e);
        }
    }

    protected function dispatch(Request $request): mixed
    {
        $result = $this->router->dispatch($request->method(), $request->uri());

        if ($result === null) {
            return new Response('Not Found', 404);
        }

        return $result;
    }

    public function addMiddleware(callable $middleware): void
    {
        $this->middleware[] = $middleware;
    }

    protected function runMiddleware(Request $request, callable $next): mixed
    {
        $pipeline = array_reduce(
            array_reverse($this->middleware),
            fn($next, $middleware) => fn($req) => $middleware($req, $next, $this->app),
            $next
        );

        return $pipeline($request);
    }

    public function getRouter(): Router
    {
        return $this->router;
    }
}
