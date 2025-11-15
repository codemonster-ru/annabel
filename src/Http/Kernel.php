<?php

namespace Codemonster\Annabel\Http;

use Codemonster\Annabel\Application;
use Codemonster\Errors\Contracts\ExceptionHandlerInterface;
use Codemonster\Router\Route;
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

            if ($response->getStatusCode() < 400) {
                return $response;
            }

            return $this->normalizeErrorResponse($response);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    protected function normalizeErrorResponse(Response $response): Response
    {
        if ($response->getContent() !== null && trim((string)$response->getContent()) !== '') {
            return $response;
        }

        return $this->handleHttpError($response->getStatusCode());
    }

    protected function handleException(Throwable $e): Response
    {
        $status = 500;

        if (method_exists($e, 'getStatusCode')) {
            /** @var object{getStatusCode: callable(): int} $e */
            $status = $e->getStatusCode();
        }

        $message = $e->getMessage() ?: 'Internal Server Error';

        try {
            $handler = $this->app->make(\Codemonster\Errors\Contracts\ExceptionHandlerInterface::class);

            return $handler->handle($e);
        } catch (Throwable $inner) {
            return $this->handleHttpError($status, $message);
        }
    }

    protected function dispatch(Request $request): mixed
    {
        $route = $this->router->dispatch($request->method(), $request->uri());

        if (!$route) {
            return $this->handleHttpError(404, 'Page not found');
        }

        return $this->runRoute($route, $request);
    }

    protected function handleHttpError(int $status, string $message = ''): Response
    {
        if ($status < 400) {
            return new Response('', $status);
        }

        try {
            $handler = $this->app->make(ExceptionHandlerInterface::class);

            $e = new class($message ?: "HTTP {$status}", $status) extends \RuntimeException {
                protected int $status;

                public function __construct(string $message, int $status)
                {
                    $this->status = $status;

                    parent::__construct($message, $status);
                }

                public function getStatusCode(): int
                {
                    return $this->status;
                }
            };

            return $handler->handle($e);
        } catch (Throwable $inner) {
            return new Response(
                sprintf("HTTP %d: %s", $status, $inner->getMessage()),
                $status,
                ['Content-Type' => 'text/plain']
            );
        }
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

    protected function runRoute(Route $route, Request $request): mixed
    {
        $handler = $route->handler;
        $middlewareList = $route->getMiddleware();

        $kernel = $this;

        $core = function (Request $req) use ($handler, $kernel) {

            if (is_array($handler)) {
                [$class, $method] = $handler;

                $controller = $kernel->app->make($class);

                return $controller->$method($req);
            }

            return $handler($req);
        };

        $pipeline = array_reduce(
            array_reverse($middlewareList),
            function ($next, $middlewareClass) use ($kernel) {
                return function (Request $req) use ($middlewareClass, $next, $kernel) {
                    $middleware = $kernel->app->make($middlewareClass);

                    return $middleware->handle($req, $next);
                };
            },
            $core
        );

        return $pipeline($request);
    }
}
