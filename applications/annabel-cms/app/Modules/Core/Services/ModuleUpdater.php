<?php

namespace Codemonster\Cms\Modules\Core\Services;

use Codemonster\Cms\Modules\Core\ModuleDefinition;
use Codemonster\Cms\Modules\Core\ModuleManager;
use Codemonster\Database\Contracts\ConnectionInterface;
use Codemonster\Database\Migrations\MigrationPathResolver;
use Codemonster\Database\Migrations\MigrationRepository;
use Codemonster\Database\Migrations\Migrator;

class ModuleUpdater
{
    public function __construct(
        private ModuleManager $modules,
        private ModuleInstallationRegistry $installations,
        private ConnectionInterface $connection,
    ) {
    }

    public function update(string $name): void
    {
        $module = $this->module($name);
        $records = $this->records();

        if (!$this->canUpdate($module, $records)) {
            throw new \RuntimeException("Module cannot be updated: {$name}");
        }

        $this->migrate($module);
        $this->installations->markUpdated($name, $module->version);
    }

    /**
     * @param array<string, array{is_enabled: bool, installed_version: ?string, installed_at: ?string, updated_at: ?string}> $records
     */
    public function canUpdate(ModuleDefinition $module, array $records): bool
    {
        if ($module->system) {
            return false;
        }

        $installedVersion = $records[$module->name]['installed_version'] ?? null;

        return is_string($installedVersion)
            && version_compare($module->version, $installedVersion, '>');
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

    /**
     * @return array<string, array{is_enabled: bool, installed_version: ?string, installed_at: ?string, updated_at: ?string}>
     */
    private function records(): array
    {
        $records = $this->installations->records();

        if ($records === null) {
            throw new \RuntimeException('Module installation registry is not available.');
        }

        return $records;
    }
}
