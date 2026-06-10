<?php

namespace Codemonster\Annabel\Container;

use Codemonster\Annabel\Container as AppContainer;
use Codemonster\Annabel\Container\Attributes\Autoconfigure;
use Codemonster\Annabel\Container\Attributes\Service;
use Codemonster\Annabel\Support\ClassDiscovery;

final class ServiceAttributeRegistrar
{
    public function __construct(
        private AppContainer $container,
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
        $interfaces = $this->interfaces($config);

        foreach ($this->discovery->discover($this->paths($config)) as $class) {
            $reflection = new \ReflectionClass($class);

            if (!$reflection->isInstantiable()) {
                continue;
            }

            $count += $this->registerServiceAttributes($class, $reflection);
            $count += $this->registerAutoconfiguredInterfaces($class, $interfaces);
        }

        return $count;
    }

    /**
     * @param class-string $class
     */
    /**
     * @param class-string $class
     * @param \ReflectionClass<object> $reflection
     */
    private function registerServiceAttributes(string $class, \ReflectionClass $reflection): int
    {
        $count = 0;

        foreach ($reflection->getAttributes(Service::class) as $attribute) {
            $service = $attribute->newInstance();
            $this->bind($service->as ?? $class, $class, $service->singleton);
            $count++;
        }

        $autoconfigure = $reflection->getAttributes(Autoconfigure::class);
        if ($autoconfigure !== []) {
            $metadata = $autoconfigure[0]->newInstance();
            $this->bind($class, $class, $metadata->singleton);
            $count++;
        }

        return $count;
    }

    /**
     * @param class-string $class
     * @param array<string, bool> $interfaces
     */
    private function registerAutoconfiguredInterfaces(string $class, array $interfaces): int
    {
        $count = 0;

        foreach ($interfaces as $interface => $singleton) {
            if (!interface_exists($interface) || !is_a($class, $interface, true)) {
                continue;
            }

            $this->bind($interface, $class, $singleton);
            $count++;
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

    /**
     * @return array<string, bool>
     */
    /**
     * @param array<string, mixed> $config
     * @return array<string, bool>
     */
    private function interfaces(array $config): array
    {
        $interfaces = $config['autoconfigure'] ?? [];

        if (!is_array($interfaces)) {
            return [];
        }

        $normalized = [];

        foreach ($interfaces as $interface => $options) {
            if (is_int($interface) && is_string($options)) {
                $normalized[$options] = false;

                continue;
            }

            if (!is_string($interface)) {
                continue;
            }

            $normalized[$interface] = is_array($options) && ($options['singleton'] ?? false) === true;
        }

        return $normalized;
    }

    private function bind(string $abstract, string $concrete, bool $singleton): void
    {
        if (!class_exists($concrete)) {
            throw new \RuntimeException("Service concrete [{$concrete}] must be an existing class.");
        }

        /** @var class-string $concrete */
        if ($singleton) {
            $this->container->singleton($abstract, $concrete);

            return;
        }

        $this->container->bind($abstract, $concrete);
    }
}
