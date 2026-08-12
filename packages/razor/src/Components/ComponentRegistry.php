<?php

declare(strict_types=1);

namespace Codemonster\Razor\Components;

use Codemonster\Razor\Components\Contracts\ComponentInterface;
use Codemonster\Razor\Components\Contracts\ComponentProviderInterface;
use Codemonster\Razor\Components\Contracts\ComponentResolverInterface;
use Codemonster\Razor\Exceptions\RazorException;

final class ComponentRegistry implements ComponentResolverInterface
{
    /** @var array<string, array<string, ComponentInterface>> */
    private array $prefixes = [];

    public function register(ComponentProviderInterface $provider): void
    {
        $this->registerPrefix($provider->prefix(), $provider->components());
    }

    /** @param array<string, ComponentInterface> $components */
    public function registerPrefix(string $prefix, array $components): void
    {
        $this->assertName($prefix, 'component prefix');

        if (isset($this->prefixes[$prefix])) {
            throw new RazorException("Razor component prefix [{$prefix}] is already registered.");
        }

        foreach ($components as $name => $component) {
            if (!is_string($name)) {
                throw new RazorException("Razor component names under prefix [{$prefix}] must be strings.");
            }

            $this->assertName($name, 'component name');

            if (!$component instanceof ComponentInterface) {
                throw new RazorException("Razor component [{$prefix}-{$name}] must implement ComponentInterface.");
            }
        }

        ksort($components);
        $this->prefixes[$prefix] = $components;
        ksort($this->prefixes);
    }

    public function handles(string $tag): bool
    {
        return $this->matchingPrefix($tag) !== null;
    }

    public function resolve(string $tag): ?ComponentInterface
    {
        $prefix = $this->matchingPrefix($tag);

        if ($prefix === null) {
            return null;
        }

        return $this->prefixes[$prefix][substr($tag, strlen($prefix) + 1)] ?? null;
    }

    public function cacheSignature(): string
    {
        $registrations = [];

        foreach ($this->prefixes as $prefix => $components) {
            $registrations[$prefix] = array_map(
                static fn (ComponentInterface $component): string => $component::class,
                $components,
            );
        }

        return hash('sha256', serialize($registrations));
    }

    private function matchingPrefix(string $tag): ?string
    {
        $matches = array_filter(
            array_keys($this->prefixes),
            static fn (string $prefix): bool => str_starts_with($tag, $prefix . '-'),
        );

        if ($matches === []) {
            return null;
        }

        usort($matches, static fn (string $left, string $right): int => strlen($right) <=> strlen($left));

        return $matches[0];
    }

    private function assertName(string $name, string $label): void
    {
        if (preg_match('/^[a-z][a-z0-9]*(?:-[a-z0-9]+)*$/D', $name) !== 1) {
            throw new RazorException("Invalid Razor {$label} [{$name}]; expected lowercase kebab case.");
        }
    }
}
