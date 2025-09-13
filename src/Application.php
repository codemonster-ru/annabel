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

    public function get(string $path, callable|array $handler): void
    {
        $this->router->add('GET', $path, $handler);
    }

    public function post(string $path, callable|array $handler): void
    {
        $this->router->add('POST', $path, $handler);
    }

    public function handle(Request $request): Response
    {
        $action = $this->router->match($request->method(), $request->uri());

        if (!$action) {
            return new Response('Not Found', 404);
        }

        if (is_array($action) && is_string($action[0])) {
            [$class, $method] = $action;
            $instance = new $class();
            $result = $instance->$method($request);
        } else {
            $result = call_user_func($action, $request);
        }

        return $result instanceof Response
            ? $result
            : new Response((string) $result);
    }

}
