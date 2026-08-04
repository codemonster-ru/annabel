<?php

namespace Codemonster\Cms\Tests\Unit\Core;

use Codemonster\Cms\Modules\Core\ModuleDefinition;
use Codemonster\Cms\Modules\Core\ModuleManager;
use Codemonster\Cms\Modules\Core\Services\ModuleInstallationRegistry;
use Codemonster\Cms\Modules\Core\Services\ModuleInstaller;
use Codemonster\Database\Contracts\ConnectionInterface;
use PHPUnit\Framework\TestCase;

class ModuleInstallerTest extends TestCase
{
    public function testModuleCanBeInstalledWhenDependenciesAreInstalled(): void
    {
        $module = new ModuleDefinition('Demo', '1.0.0', '/modules/Demo', ['Core']);
        $installer = $this->installer(['Demo' => $module]);

        self::assertTrue($installer->canInstall($module, ['Core' => true]));
        self::assertFalse($installer->canInstall($module, []));
    }

    public function testOnlyDisabledModuleWithoutInstalledDependentsCanBeUninstalled(): void
    {
        $module = new ModuleDefinition('Demo', '1.0.0', '/modules/Demo');
        $dependent = new ModuleDefinition('Dependent', '1.0.0', '/modules/Dependent', ['Demo']);
        $installer = $this->installer(['Demo' => $module, 'Dependent' => $dependent]);

        self::assertTrue($installer->canUninstall($module, ['Demo' => false]));
        self::assertFalse($installer->canUninstall($module, ['Demo' => true]));
        self::assertFalse($installer->canUninstall($module, ['Demo' => false, 'Dependent' => false]));
    }

    public function testInstallRegistersModuleAsDisabledAfterRunningItsResources(): void
    {
        $module = new ModuleDefinition('Demo', '1.0.0', '/missing/modules/Demo');
        $modules = $this->moduleManager(['Demo' => $module]);
        $installations = $this->createMock(ModuleInstallationRegistry::class);
        $installations->method('states')->willReturn([]);
        $installations->expects(self::once())->method('install')->with('Demo', '1.0.0');

        (new ModuleInstaller(
            $modules,
            $installations,
            $this->createStub(ConnectionInterface::class),
        ))->install('Demo');
    }

    public function testUninstallRemovesDisabledModuleRegistrationWithoutDeletingData(): void
    {
        $module = new ModuleDefinition('Demo', '1.0.0', '/missing/modules/Demo');
        $modules = $this->moduleManager(['Demo' => $module]);
        $installations = $this->createMock(ModuleInstallationRegistry::class);
        $installations->method('states')->willReturn(['Demo' => false]);
        $installations->expects(self::once())->method('uninstall')->with('Demo');

        (new ModuleInstaller(
            $modules,
            $installations,
            $this->createStub(ConnectionInterface::class),
        ))->uninstall('Demo');
    }

    /** @param array<string, ModuleDefinition> $definitions */
    private function installer(array $definitions): ModuleInstaller
    {
        return new ModuleInstaller(
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
