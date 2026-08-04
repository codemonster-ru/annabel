<?php

namespace Codemonster\Cms\Tests\Unit\Core;

use Codemonster\Cms\Modules\Core\ModuleDefinition;
use Codemonster\Cms\Modules\Core\ModuleManager;
use Codemonster\Cms\Modules\Core\Services\ModuleInstallationRegistry;
use Codemonster\Cms\Modules\Core\Services\ModuleUpdater;
use Codemonster\Database\Contracts\ConnectionInterface;
use PHPUnit\Framework\TestCase;

class ModuleUpdaterTest extends TestCase
{
    public function testInstalledNonSystemModuleCanBeUpdatedToNewerVersion(): void
    {
        $module = new ModuleDefinition('Demo', '1.1.0', '/modules/Demo');
        $updater = $this->updater(['Demo' => $module]);

        self::assertTrue($updater->canUpdate($module, [
            'Demo' => $this->record('1.0.0'),
        ]));
        self::assertFalse($updater->canUpdate($module, [
            'Demo' => $this->record('1.1.0'),
        ]));
        self::assertFalse($updater->canUpdate($module, []));
    }

    public function testSystemModuleCannotBeUpdatedFromAdminLifecycle(): void
    {
        $module = new ModuleDefinition('Core', '1.1.0', '/modules/Core', system: true);

        self::assertFalse($this->updater(['Core' => $module])->canUpdate($module, [
            'Core' => $this->record('1.0.0'),
        ]));
    }

    public function testUpdateRecordsVersionAfterRunningPendingMigrations(): void
    {
        $module = new ModuleDefinition('Demo', '1.1.0', '/missing/modules/Demo');
        $installations = $this->createMock(ModuleInstallationRegistry::class);
        $installations->method('records')->willReturn([
            'Demo' => $this->record('1.0.0'),
        ]);
        $installations->expects(self::once())->method('markUpdated')->with('Demo', '1.1.0');

        (new ModuleUpdater(
            $this->moduleManager(['Demo' => $module]),
            $installations,
            $this->createStub(ConnectionInterface::class),
        ))->update('Demo');
    }

    /**
     * @return array{is_enabled: bool, installed_version: string, installed_at: ?string, updated_at: ?string}
     */
    private function record(string $version): array
    {
        return [
            'is_enabled' => false,
            'installed_version' => $version,
            'installed_at' => null,
            'updated_at' => null,
        ];
    }

    /** @param array<string, ModuleDefinition> $definitions */
    private function updater(array $definitions): ModuleUpdater
    {
        return new ModuleUpdater(
            $this->moduleManager($definitions),
            $this->createStub(ModuleInstallationRegistry::class),
            $this->createStub(ConnectionInterface::class),
        );
    }

    /** @param array<string, ModuleDefinition> $definitions */
    private function moduleManager(array $definitions): ModuleManager
    {
        $modules = $this->createStub(ModuleManager::class);
        $modules->method('availableDefinitions')->willReturn($definitions);

        return $modules;
    }
}
