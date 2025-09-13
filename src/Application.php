<?php

namespace Annabel;

use Annabel\Http\Request;
use Annabel\Http\Response;
use Annabel\Routing\Router;
use Annabel\View\View;
use Annabel\View\Engines\PhpEngine;
use Annabel\View\Engines\SsrEngine;
use Annabel\SSR\Bridge;

class Application
{
    protected static ?Application $instance = null;
    protected Router $router;
    protected View $view;
    protected string $basePath;

    public function __construct(?string $basePath = null, bool $enableSsr = false)
    {
        $this->basePath = $basePath ?? dirname(__DIR__);
        $this->router = new Router();

        $phpEngine = new PhpEngine("$this->basePath/resources/views");
        $ssrEngine = $enableSsr
            ? new SsrEngine(new Bridge())
            : null;

        $this->view = new View($phpEngine, $ssrEngine);

        self::$instance = $this;
    }

    public static function getInstance(): Application
    {
        if (!self::$instance) {
            throw new \RuntimeException("Application instance is not initialized");
        }

        return self::$instance;
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    public function get(string $path, callable|array $handler): void
    {
        $this->router->add('GET', $path, $handler);
    }

    public function post(string $path, callable|array $handler): void
    {
        $this->router->add('POST', $path, $handler);
    }

    public function view(string $template, array $data = []): Response
    {
        $content = $this->view->render($template, $data);

        return new Response($content);
    }

    public function vue(string $component, array $props = []): Response
    {
        $content = $this->view->render($component, $props, true);

        return new Response($content);
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
