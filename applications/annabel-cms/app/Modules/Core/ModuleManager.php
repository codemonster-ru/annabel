<?php

namespace Codemonster\Cms\Modules\Core;

use Codemonster\Annabel\Application;
use Codemonster\Annabel\Contracts\ServiceProviderInterface;

class ModuleManager
{
    /** @var array<string, ModuleDefinition> */
    private array $modules = [];

    /** @var array<string, ServiceProviderInterface> */
    private array $providers = [];

    private bool $booted = false;

    public function __construct(
        private Application $app,
    ) {
    }

    /**
     * @param array<int, string> $exclude
     */
    public function bootAll(array $exclude = []): void
    {
        if ($this->booted) {
            return;
        }

        $excluded = array_fill_keys($exclude, true);
        $disabled = array_fill_keys((array) config('modules.disabled', []), true);
        $modules = [];

        foreach ($this->discover() as $name => $module) {
            if (isset($excluded[$name]) || isset($disabled[$name])) {
                continue;
            }

            $modules[$name] = $module;
        }

        $this->modules = $this->sortByDependencies($modules);
        $this->registerViews();
        $this->registerProviders();
        $this->registerRoutes();
        $this->bootProviders();
        $this->booted = true;
    }

    /**
     * @return array<string, array{name: string, version: string, dependencies: array<int, string>, assets: array<string, mixed>}>
     */
    public function all(): array
    {
        $result = [];

        foreach ($this->modules as $name => $module) {
            $result[$name] = [
                'name' => $module->name,
                'version' => $module->version,
                'dependencies' => $module->dependencies,
                'assets' => $module->assets,
            ];
        }

        return $result;
    }

    /**
     * @return array<string, string>
     */
    public function listAll(): array
    {
        $result = [];

        foreach ($this->modules as $name => $module) {
            $result[$name] = $module->version;
        }

        return $result;
    }

    /**
     * @return array<string, ModuleDefinition>
     */
    private function discover(): array
    {
        $modules = [];
        $modulesPath = $this->app->getBasePath() . '/app/Modules';

        foreach (glob($modulesPath . '/*', GLOB_ONLYDIR) ?: [] as $modulePath) {
            $manifestPath = $modulePath . '/module.php';

            if (!is_file($manifestPath)) {
                throw new \RuntimeException("Module manifest not found: {$manifestPath}");
            }

            $manifest = require $manifestPath;

            if (!is_array($manifest)) {
                throw new \RuntimeException("Module manifest must return an array: {$manifestPath}");
            }

            $module = $this->definition($modulePath, $manifest);

            if (isset($modules[$module->name])) {
                throw new \RuntimeException("Duplicate module name: {$module->name}");
            }

            $modules[$module->name] = $module;
        }

        ksort($modules);

        return $modules;
    }

    /**
     * @param array<string, mixed> $manifest
     */
    private function definition(string $modulePath, array $manifest): ModuleDefinition
    {
        $name = $manifest['name'] ?? null;
        $version = $manifest['version'] ?? null;
        $dependencies = $manifest['dependencies'] ?? [];
        $provider = $manifest['provider'] ?? null;
        $routes = $manifest['routes'] ?? 'routes/web.php';
        $views = $manifest['views'] ?? 'views';
        $assets = $manifest['assets'] ?? [];

        if (!is_string($name) || $name === '') {
            throw new \RuntimeException("Module name is required: {$modulePath}");
        }

        if (!is_string($version) || $version === '') {
            throw new \RuntimeException("Module version is required: {$name}");
        }

        if (!is_array($dependencies) || array_filter($dependencies, fn ($item) => !is_string($item))) {
            throw new \RuntimeException("Module dependencies must be strings: {$name}");
        }

        if ($provider !== null && (!is_string($provider) || !is_subclass_of($provider, ServiceProviderInterface::class))) {
            throw new \RuntimeException("Invalid service provider for module: {$name}");
        }

        if (!is_array($assets)) {
            throw new \RuntimeException("Module assets must be an array: {$name}");
        }

        return new ModuleDefinition(
            $name,
            $version,
            $modulePath,
            array_values($dependencies),
            $provider,
            is_string($routes) ? $routes : null,
            is_string($views) ? $views : null,
            $assets,
        );
    }

    /**
     * @param array<string, ModuleDefinition> $modules
     * @return array<string, ModuleDefinition>
     */
    private function sortByDependencies(array $modules): array
    {
        $sorted = [];
        $visiting = [];

        $visit = function (string $name) use (&$visit, &$sorted, &$visiting, $modules): void {
            if (isset($sorted[$name])) {
                return;
            }

            if (isset($visiting[$name])) {
                throw new \RuntimeException("Circular module dependency detected at: {$name}");
            }

            $module = $modules[$name] ?? null;

            if (!$module) {
                throw new \RuntimeException("Required module is disabled or missing: {$name}");
            }

            $visiting[$name] = true;

            foreach ($module->dependencies as $dependency) {
                $visit($dependency);
            }

            unset($visiting[$name]);
            $sorted[$name] = $module;
        };

        foreach (array_keys($modules) as $name) {
            $visit($name);
        }

        return $sorted;
    }

    private function registerViews(): void
    {
        foreach ($this->modules as $module) {
            $viewsPath = $module->resolve($module->views);

            if ($viewsPath && is_dir($viewsPath)) {
                $this->app->getView()->addNamespace(strtolower($module->name), $viewsPath);
            }
        }
    }

    private function registerProviders(): void
    {
        foreach ($this->modules as $name => $module) {
            if ($module->provider === null) {
                continue;
            }

            $provider = new $module->provider($this->app);
            $provider->register();
            $this->app->addProvider($provider);
            $this->providers[$name] = $provider;
        }
    }

    private function registerRoutes(): void
    {
        foreach ($this->modules as $module) {
            $routesPath = $module->resolve($module->routes);

            if ($routesPath && is_file($routesPath)) {
                require $routesPath;
            }
        }
    }

    private function bootProviders(): void
    {
        foreach ($this->providers as $provider) {
            $provider->boot();
        }
    }
}
