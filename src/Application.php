<?php

namespace Annabel;

use Annabel\Http\Request;
use Annabel\Http\Response;
use Annabel\Routing\Router;

class Application
{
    protected Router $router;

    public function __construct()
    {
        $this->router = new Router();
    }

    public function get(string $uri, callable $action): void
    {
        $this->router->add('GET', $uri, $action);
    }

    public function post(string $uri, callable $action): void
    {
        $this->router->add('POST', $uri, $action);
    }

    public function handle(Request $request): Response
    {
        $action = $this->router->match($request->method(), $request->uri());

        if (!$action) {
            return new Response('Not Found', 404);
        }

        $result = call_user_func($action, $request);

        return $result instanceof Response
            ? $result
            : new Response((string) $result);
    }
}
