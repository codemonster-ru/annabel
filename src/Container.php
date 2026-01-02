<?php

namespace Codemonster\Annabel;

use Closure;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionNamedType;

class Container
{
    protected array $bindings = [];
    protected array $instances = [];

    public function bind(string $abstract, Closure|string $concrete, bool $singleton = false): void
    {
        if ($abstract === $concrete) {
            $concrete = fn($container) => $container->build($abstract);
        }

        $this->bindings[$abstract] = compact('concrete', 'singleton');
    }

    public function singleton(string $abstract, Closure|string $concrete): void
    {
        $this->bind($abstract, $concrete, true);
    }

    public function instance(string $abstract, object $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    public function has(string $abstract): bool
    {
        return isset($this->instances[$abstract]) || isset($this->bindings[$abstract]);
    }

    public function make(string $abstract, array $parameters = []): mixed
    {
        if (isset($this->instances[$abstract])) {
            if ($parameters !== []) {
                throw new \RuntimeException(
                    "Singleton [$abstract] is already resolved; parameters are ignored."
                );
            }

            return $this->instances[$abstract];
        }

        if (isset($this->bindings[$abstract])) {
            $binding = $this->bindings[$abstract];
            $concrete = $binding['concrete'];

            if ($concrete instanceof Closure) {
                $reflection = new ReflectionFunction($concrete);
                $paramCount = $reflection->getNumberOfParameters();
                $object = $paramCount >= 2
                    ? $concrete($this, $parameters)
                    : $concrete($this);
            } else {
                $object = $this->build($concrete, $parameters);
            }

            if ($binding['singleton']) {
                $this->instances[$abstract] = $object;
            }

            return $object;
        }

        return $this->build($abstract, $parameters);
    }

    public function build(string $class, array $parameters = []): object
    {
        try {
            $reflector = new ReflectionClass($class);

            if (!$reflector->isInstantiable()) {
                throw new \RuntimeException("Class [$class] is not instantiable.");
            }

            $constructor = $reflector->getConstructor();

            if (!$constructor) {
                return new $class;
            }

            $dependencies = [];

            foreach ($constructor->getParameters() as $param) {
                $name = $param->getName();

                if (array_key_exists($name, $parameters)) {
                    $dependencies[] = $parameters[$name];

                    continue;
                }

                $type = $param->getType();

                if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                    $dependencies[] = $this->make($type->getName());
                } elseif ($param->isDefaultValueAvailable()) {
                    $dependencies[] = $param->getDefaultValue();
                } else {
                    throw new \RuntimeException(
                        "Unresolvable dependency [{$param->getName()}] in [$class]"
                    );
                }
            }

            return $reflector->newInstanceArgs($dependencies);
        } catch (ReflectionException $e) {
            throw new \RuntimeException("Unable to build [$class]: {$e->getMessage()}");
        }
    }

    public function call(Closure $callable, array $parameters = []): mixed
    {
        $reflection = new ReflectionFunction($callable);
        $args = [];

        foreach ($reflection->getParameters() as $param) {
            $name = $param->getName();
            $type = $param->getType();

            if (array_key_exists($name, $parameters)) {
                $args[] = $parameters[$name];
            } elseif ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $args[] = $this->make($type->getName());
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } else {
                throw new \RuntimeException("Unable to resolve parameter [$name] for callable.");
            }
        }

        return $callable(...$args);
    }

    /**
     * Expose registered bindings for diagnostic/CLI purposes.
     *
     * @return array<string, array{concrete: Closure|string, singleton: bool}>
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }

    /**
     * Expose instantiated singletons for diagnostic/CLI purposes.
     *
     * @return array<string, object>
     */
    public function getInstances(): array
    {
        return $this->instances;
    }
}
