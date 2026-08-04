<?php

namespace Codemonster\Cms\Modules\Core\Services;

use Codemonster\Cms\Modules\Core\ModuleDefinition;
use Codemonster\Cms\Modules\Core\ModuleManager;
use Codemonster\Database\Contracts\ConnectionInterface;
use Codemonster\Database\Migrations\MigrationPathResolver;
use Codemonster\Database\Migrations\MigrationRepository;
use Codemonster\Database\Migrations\Migrator;
use Codemonster\Database\Seeders\SeederRunner;
use Codemonster\Database\Seeders\SeedPathResolver;

class ModuleInstaller
{
    public function __construct(
        private ModuleManager $modules,
        private ModuleInstallationRegistry $installations,
        private ConnectionInterface $connection,
    ) {
    }

    public function install(string $name): void
    {
        $module = $this->module($name);
        $states = $this->states();

        if (!$this->canInstall($module, $states)) {
            throw new \RuntimeException("Module cannot be installed: {$name}");
        }

        $this->migrate($module);
        $this->seed($module);
        $this->installations->install($name, $module->version);
    }

    public function uninstall(string $name): void
    {
        $module = $this->module($name);
        $states = $this->states();

        if (!$this->canUninstall($module, $states)) {
            throw new \RuntimeException("Module cannot be uninstalled: {$name}");
        }

        $this->installations->uninstall($name);
    }

    /** @param array<string, bool> $states */
    public function canInstall(ModuleDefinition $module, array $states): bool
    {
        if ($module->system || array_key_exists($module->name, $states)) {
            return false;
        }

        foreach ($module->dependencies as $dependency) {
            if (!array_key_exists($dependency, $states)) {
                return false;
            }
        }

        return true;
    }

    /** @param array<string, bool> $states */
    public function canUninstall(ModuleDefinition $module, array $states): bool
    {
        if ($module->system || ($states[$module->name] ?? null) !== false) {
            return false;
        }

        foreach ($this->modules->availableDefinitions() as $dependent) {
            if (array_key_exists($dependent->name, $states)
                && in_array($module->name, $dependent->dependencies, true)
            ) {
                return false;
            }
        }

        return true;
    }

    private function migrate(ModuleDefinition $module): void
    {
        $path = $module->resolve($module->migrations);

        if ($path === null || !is_dir($path)) {
            return;
        }

        $paths = new MigrationPathResolver();
        $paths->addPath($path);
        (new Migrator($this->repository(), $this->connection, $paths))->migrate();
    }

    private function seed(ModuleDefinition $module): void
    {
        $path = $module->resolve($module->seeds);

        if ($path === null || !is_dir($path)) {
            return;
        }

        $paths = new SeedPathResolver();
        $paths->addPath($path);
        (new SeederRunner($this->connection, $paths))->seed();
    }

    private function repository(): MigrationRepository
    {
        return new MigrationRepository(
            $this->connection,
            (string) config('database.migrations.table', 'migrations'),
        );
    }

    private function module(string $name): ModuleDefinition
    {
        $module = $this->modules->availableDefinitions()[$name] ?? null;

        if (!$module instanceof ModuleDefinition) {
            throw new \RuntimeException("Module not found: {$name}");
        }

        return $module;
    }

    /** @return array<string, bool> */
    private function states(): array
    {
        $states = $this->installations->states();

        if ($states === null) {
            throw new \RuntimeException('Module installation registry is not available.');
        }

        return $states;
    }
}
