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
        $modules = [];

        foreach ($this->discover() as $name => $module) {
            if (isset($excluded[$name])) {
                continue;
            }

            $modules[$name] = $module;
        }

        $this->modules = $this->sortByDependencies($modules);
        $this->registerDatabasePaths();
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
    public function definitions(): array
    {
        return $this->modules;
    }

    /**
     * @return array<int, string>
     */
    public function migrationPaths(): array
    {
        return $this->modulePaths('migrations');
    }

    /**
     * @return array<int, string>
     */
    public function seedPaths(): array
    {
        return $this->modulePaths('seeds');
    }

    /**
     * @return array<string, ModuleDefinition>
     */
    private function discover(): array
    {
        $modules = [];
        $modulesPath = realpath($this->app->getBasePath() . '/app/Modules')
            ?: $this->app->getBasePath() . '/app/Modules';

        foreach (glob($modulesPath . '/*', GLOB_ONLYDIR) ?: [] as $modulePath) {
            $modulePath = realpath($modulePath) ?: $modulePath;
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
        $assets = $manifest['assets'] ?? [];

        if (!is_string($name) || $name === '') {
            throw new \RuntimeException("Module name is required: {$modulePath}");
        }

        if (!is_string($version) || $version === '') {
            throw new \RuntimeException("Module version is required: {$name}");
        }

        if (!is_array($dependencies)) {
            throw new \RuntimeException("Module dependencies must be strings: {$name}");
        }

        foreach ($dependencies as $dependency) {
            if (!is_string($dependency) || $dependency === '') {
                throw new \RuntimeException("Module dependencies must be non-empty strings: {$name}");
            }
        }

        if ($provider !== null && (!is_string($provider) || !is_subclass_of($provider, ServiceProviderInterface::class))) {
            throw new \RuntimeException("Invalid service provider for module: {$name}");
        }

        if (!is_array($assets)) {
            throw new \RuntimeException("Module assets must be an array: {$name}");
        }

        $routes = $this->optionalPath($manifest, 'routes', 'routes/web.php', $name);
        $views = $this->optionalPath($manifest, 'views', 'views', $name);
        $migrations = $this->optionalPath($manifest, 'migrations', null, $name);
        $seeds = $this->optionalPath($manifest, 'seeds', null, $name);

        $metadata = array_diff_key($manifest, array_flip([
            'name',
            'version',
            'dependencies',
            'provider',
            'routes',
            'views',
            'migrations',
            'seeds',
            'assets',
        ]));

        return new ModuleDefinition(
            $name,
            $version,
            $modulePath,
            array_values($dependencies),
            $provider,
            $routes,
            $views,
            $migrations,
            $seeds,
            $assets,
            $metadata,
        );
    }

    /**
     * @param array<string, mixed> $manifest
     */
    private function optionalPath(array $manifest, string $key, ?string $default, string $module): ?string
    {
        if (!array_key_exists($key, $manifest)) {
            return $default;
        }

        $value = $manifest[$key];

        if ($value === null) {
            return null;
        }

        if (!is_string($value) || $value === '') {
            throw new \RuntimeException("Module {$key} path must be a non-empty string or null: {$module}");
        }

        return $value;
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

    private function registerDatabasePaths(): void
    {
        config([
            'database.migrations.paths' => $this->migrationPaths(),
            'database.seeds.paths' => $this->seedPaths(),
        ]);
    }

    /**
     * @param 'migrations'|'seeds' $property
     * @return array<int, string>
     */
    private function modulePaths(string $property): array
    {
        $paths = [];

        foreach ($this->modules as $module) {
            $path = $module->resolve($module->{$property});

            if ($path !== null && is_dir($path)) {
                $paths[] = $path;
            }
        }

        return array_values(array_unique($paths));
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
