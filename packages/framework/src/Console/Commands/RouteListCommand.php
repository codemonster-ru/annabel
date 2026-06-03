<?php

namespace Codemonster\Annabel\Console\Commands;

use Codemonster\Annabel\Console\Command;
use Codemonster\Router\Route;
use Codemonster\Router\RouteCollection;
use Codemonster\Router\Router;
use ReflectionProperty;

class RouteListCommand extends Command
{
    public function getName(): string
    {
        return 'route:list';
    }

    public function getAliases(): array
    {
        return ['routes'];
    }

    public function getDescription(): string
    {
        return 'Show registered routes (method, URI, handler, middleware).';
    }

    public function getUsage(): string
    {
        return 'route:list';
    }

    public function handle(array $arguments = []): int
    {
        $console = $this->console();
        $app = $console->getApplication();
        $router = $app->getKernel()->getRouter();

        $routes = $this->extractRoutes($router);

        if (empty($routes)) {
            $console->writeln($console->color('No routes registered.', 'muted'));

            return 0;
        }

        $console->writeln($console->color('Routes:', 'label'));
        $console->writeln(sprintf(
            '  %s  %s  %s  %s',
            str_pad('Method', 10),
            str_pad('URI', 30),
            str_pad('Handler', 30),
            'Middleware'
        ));

        foreach ($routes as $route) {
            $methods = implode('|', $route->methods);
            $handler = $this->describeHandler($route->handler);
            $middleware = $this->describeMiddleware($route->getMiddleware());

            $console->writeln(sprintf(
                '  %s  %s  %s  %s',
                str_pad($methods, 10),
                str_pad($route->path, 30),
                str_pad($handler, 30),
                $middleware
            ));
        }

        return 0;
    }

    /**
     * @return Route[]
     */
    protected function extractRoutes(Router $router): array
    {
        $routesProperty = new ReflectionProperty($router, 'routes');
        $routesProperty->setAccessible(true);

        /** @var RouteCollection $collection */
        $collection = $routesProperty->getValue($router);

        $collectionProperty = new ReflectionProperty($collection, 'routes');
        $collectionProperty->setAccessible(true);

        /** @var Route[] $routes */
        $routes = $collectionProperty->getValue($collection);

        return $routes;
    }

    protected function describeHandler(mixed $handler): string
    {
        if (is_array($handler) && count($handler) === 2) {
            [$class, $method] = $handler;

            return is_string($class) ? $class . '@' . $method : 'callable';
        }

        if ($handler instanceof \Closure) {
            return 'Closure';
        }

        if (is_object($handler)) {
            return get_class($handler);
        }

        return (string)$handler;
    }

    protected function describeMiddleware(array $middleware): string
    {
        if (empty($middleware)) {
            return '-';
        }

        $names = [];

        foreach ($middleware as $entry) {
            if (is_array($entry)) {
                $class = $entry[0] ?? '';
                $param = $entry[1] ?? null;
                $names[] = $param ? "{$class}:{$param}" : (string)$class;
            } else {
                $names[] = (string)$entry;
            }
        }

        return implode(', ', $names);
    }
}
