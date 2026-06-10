<?php

namespace Codemonster\Annabel;

use Closure;
use Codemonster\Annabel\Exceptions\ContainerException;
use Codemonster\Annabel\Exceptions\NotFoundException;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionNamedType;

class Container implements ContainerInterface
{
    /** @var array<string, array{concrete: Closure|class-string, singleton: bool}> */
    protected array $bindings = [];
    /** @var array<string, mixed> */
    protected array $instances = [];

    /** @param Closure(self, array<string, mixed>=): mixed|class-string $concrete */
    public function bind(string $abstract, Closure|string $concrete, bool $singleton = false): void
    {
        if ($abstract === $concrete) {
            $concrete = fn (self $container): object => $container->build($abstract);
        }

        $this->bindings[$abstract] = compact('concrete', 'singleton');
    }

    /** @param Closure(self, array<string, mixed>=): mixed|class-string $concrete */
    public function singleton(string $abstract, Closure|string $concrete): void
    {
        $this->bind($abstract, $concrete, true);
    }

    public function instance(string $abstract, mixed $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    public function has(string $abstract): bool
    {
        if (isset($this->instances[$abstract]) || isset($this->bindings[$abstract])) {
            return true;
        }

        return class_exists($abstract) && (new ReflectionClass($abstract))->isInstantiable();
    }

    public function get(string $id): mixed
    {
        return $this->make($id);
    }

    /**
     * @template T of object
     * @param class-string<T>|string $abstract
     * @param array<string, mixed> $parameters
     * @return ($abstract is class-string<T> ? T : mixed)
     */
    public function make(string $abstract, array $parameters = []): mixed
    {
        if (isset($this->instances[$abstract])) {
            if ($parameters !== []) {
                throw new ContainerException(
                    "Singleton [$abstract] is already resolved; parameters are ignored.",
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

    /** @param array<string, mixed> $parameters */
    public function build(string $class, array $parameters = []): object
    {
        if (!class_exists($class)) {
            throw new NotFoundException("Unable to build [$class]: class does not exist.");
        }

        try {
            /** @var class-string $class */
            $reflector = new ReflectionClass($class);

            if (!$reflector->isInstantiable()) {
                throw new ContainerException("Class [$class] is not instantiable.");
            }

            $constructor = $reflector->getConstructor();

            if (!$constructor) {
                return new $class();
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
                    throw new ContainerException(
                        "Unresolvable dependency [{$param->getName()}] in [$class]",
                    );
                }
            }

            return $reflector->newInstanceArgs($dependencies);
        } catch (ReflectionException $e) {
            throw new NotFoundException("Unable to build [$class]: {$e->getMessage()}", previous: $e);
        }
    }

    /**
     * @param array<string, mixed> $parameters
     */
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
                throw new ContainerException("Unable to resolve parameter [$name] for callable.");
            }
        }

        return $callable(...$args);
    }

    /**
     * Expose registered bindings for diagnostic/CLI purposes.
     *
     * @return array<string, array{concrete: Closure|class-string, singleton: bool}>
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }

    /**
     * Expose instantiated singletons for diagnostic/CLI purposes.
     *
     * @return array<string, mixed>
     */
    public function getInstances(): array
    {
        return $this->instances;
    }
}
