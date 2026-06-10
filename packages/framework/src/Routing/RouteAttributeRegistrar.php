<?php

namespace Codemonster\Annabel\Routing;

use Codemonster\Annabel\Routing\Attributes\Middleware;
use Codemonster\Annabel\Routing\Attributes\Route;
use Codemonster\Annabel\Routing\Attributes\RoutePrefix;
use Codemonster\Annabel\Support\ClassDiscovery;
use Codemonster\Router\Router;

final class RouteAttributeRegistrar
{
    public function __construct(
        private Router $router,
        private ClassDiscovery $discovery = new ClassDiscovery(),
    ) {
    }

    /**
     * @param array<string, mixed> $config
     */
    public function register(array $config): int
    {
        if (($config['enabled'] ?? false) !== true) {
            return 0;
        }

        $count = 0;

        foreach ($this->discovery->discover($this->paths($config)) as $class) {
            $count += $this->registerClass($class);
        }

        return $count;
    }

    /**
     * @param class-string $class
     */
    private function registerClass(string $class): int
    {
        $reflection = new \ReflectionClass($class);

        if (!$reflection->isInstantiable()) {
            return 0;
        }

        $prefix = $this->prefix($reflection);
        $classMiddleware = $this->middleware($reflection);
        $count = 0;

        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isStatic() || $method->isConstructor() || $method->isDestructor()) {
                continue;
            }

            $methodMiddleware = $this->middleware($method);

            foreach ($method->getAttributes(Route::class, \ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
                $metadata = $attribute->newInstance();
                $route = $this->router->add($metadata->methods, $this->join($prefix, $metadata->path), [
                    $class,
                    $method->getName(),
                ]);

                if ($metadata->name !== null) {
                    $route->name($metadata->name);
                }

                if ($metadata->where !== []) {
                    $route->where($metadata->where);
                }

                foreach (array_merge($classMiddleware, $methodMiddleware, $this->normalizeMiddleware($metadata->middleware)) as $middleware) {
                    if (!is_string($middleware) && !is_array($middleware)) {
                        continue;
                    }

                    $route->middleware($middleware);
                }

                $count++;
            }
        }

        return $count;
    }

    /**
     * @return list<string>
     */
    /**
     * @param array<string, mixed> $config
     * @return list<string>
     */
    private function paths(array $config): array
    {
        $paths = $config['paths'] ?? $config['path'] ?? [];
        $paths = is_string($paths) ? [$paths] : $paths;

        if (!is_array($paths)) {
            return [];
        }

        return array_values(array_filter($paths, 'is_string'));
    }

    /** @param \ReflectionClass<object> $class */
    private function prefix(\ReflectionClass $class): string
    {
        $attributes = $class->getAttributes(RoutePrefix::class);

        if ($attributes === []) {
            return '';
        }

        return $attributes[0]->newInstance()->prefix;
    }

    /**
     * @param \ReflectionClass<object>|\ReflectionMethod $reflection
     * @return list<string|array<mixed>>
     */
    private function middleware(\ReflectionClass|\ReflectionMethod $reflection): array
    {
        $middleware = [];

        foreach ($reflection->getAttributes(Middleware::class) as $attribute) {
            $metadata = $attribute->newInstance();
            $middleware[] = $metadata->parameter === null ? $metadata->name : [$metadata->name, $metadata->parameter];
        }

        return $middleware;
    }

    /**
     * @param string|list<string|array<mixed>> $middleware
     * @return list<string|array<mixed>>
     */
    private function normalizeMiddleware(string|array $middleware): array
    {
        if (is_string($middleware)) {
            return $middleware === '' ? [] : [$middleware];
        }

        return array_values($middleware);
    }

    private function join(string $prefix, string $path): string
    {
        $prefix = trim($prefix, '/');
        $path = trim($path, '/');

        if ($prefix === '' && $path === '') {
            return '/';
        }

        return '/' . implode('/', array_filter([$prefix, $path], fn (string $part): bool => $part !== ''));
    }
}
