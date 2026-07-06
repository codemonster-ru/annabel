<?php

declare(strict_types=1);

namespace Codemonster\Support\Tests;

class SupportFakeContainer
{
    /** @var array<string, callable(self): mixed> */
    protected array $bindings = [];
    /** @var array<string, mixed> */
    protected array $instances = [];

    /**
     * Bind a lazy factory (executed on make()).
     */
    public function bind(string $abstract, callable $factory): void
    {
        $this->bindings[$abstract] = $factory;
    }

    /**
     * Register a ready singleton instance.
     *
     * Used by helpers like:
     * app()->instance(Class::class, $object)
     */
    public function instance(string $abstract, mixed $object): void
    {
        $this->instances[$abstract] = $object;
    }

    /**
     * Register a singleton.
     *
     * IMPORTANT:
     * For SUPPORT package tests we use *eager* singletons,
     * because request(), view(), db() rely on immediate creation.
     */
    public function singleton(string $abstract, callable $factory): void
    {
        $this->instances[$abstract] = $factory($this);
    }

    public function has(string $abstract): bool
    {
        return isset($this->instances[$abstract]) || isset($this->bindings[$abstract]);
    }

    /**
     * Resolve dependency.
     */
    public function make(string $abstract): mixed
    {
        // Return existing singleton instance
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        // Resolve lazy factory
        if (isset($this->bindings[$abstract])) {
            $factory = $this->bindings[$abstract];

            return $factory($this);
        }

        // Auto-resolve class names
        if (class_exists($abstract)) {
            return new $abstract();
        }

        throw new \RuntimeException("Binding [$abstract] not found in fake container.");
    }

    public function reset(): void
    {
        $this->bindings = [];
        $this->instances = [];
    }
}
