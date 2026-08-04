<?php

namespace Codemonster\Cms\Tests\Unit\Core;

use Codemonster\Cms\Modules\Core\ModuleDefinition;
use Codemonster\Cms\Modules\Core\ModuleManager;
use Codemonster\Cms\Modules\Core\Services\ModuleActivationManager;
use Codemonster\Cms\Modules\Core\Services\ModuleInstallationRegistry;
use PHPUnit\Framework\TestCase;

class ModuleActivationManagerTest extends TestCase
{
    public function testItEnablesInstalledModuleWhenDependenciesAreEnabled(): void
    {
        $core = new ModuleDefinition('Core', '1.0.0', '/modules/Core');
        $pages = new ModuleDefinition('Pages', '1.0.0', '/modules/Pages', ['Core']);
        $modules = $this->modules(['Core' => $core, 'Pages' => $pages]);
        $installations = $this->createMock(ModuleInstallationRegistry::class);
        $installations->method('states')->willReturn(['Core' => true, 'Pages' => false]);
        $installations->expects(self::once())->method('enable')->with('Pages');
        $activation = new ModuleActivationManager($modules, $installations);

        self::assertTrue($activation->canEnable($pages, ['Core' => true, 'Pages' => false]));
        $activation->enable('Pages');
    }

    public function testItRejectsDisablingModuleRequiredByEnabledModule(): void
    {
        $pages = new ModuleDefinition('Pages', '1.0.0', '/modules/Pages');
        $adminPages = new ModuleDefinition('AdminPages', '1.0.0', '/modules/AdminPages', ['Pages']);
        $modules = $this->modules(['Pages' => $pages, 'AdminPages' => $adminPages]);
        $installations = $this->createStub(ModuleInstallationRegistry::class);
        $installations->method('states')->willReturn(['Pages' => true, 'AdminPages' => true]);
        $activation = new ModuleActivationManager($modules, $installations);

        self::assertFalse($activation->canDisable($pages, ['Pages' => true, 'AdminPages' => true]));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Module is required by enabled module: AdminPages');

        $activation->disable('Pages');
    }

    public function testItRejectsDisablingSystemModule(): void
    {
        $core = new ModuleDefinition(
            'Core',
            '1.0.0',
            '/modules/Core',
            system: true,
        );
        $modules = $this->modules(['Core' => $core]);
        $installations = $this->createStub(ModuleInstallationRegistry::class);
        $installations->method('states')->willReturn(['Core' => true]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('System module cannot be disabled: Core');

        (new ModuleActivationManager($modules, $installations))->disable('Core');
    }

    public function testItRejectsEnablingSystemModuleManually(): void
    {
        $core = new ModuleDefinition(
            'Core',
            '1.0.0',
            '/modules/Core',
            system: true,
        );
        $modules = $this->modules(['Core' => $core]);
        $installations = $this->createStub(ModuleInstallationRegistry::class);
        $installations->method('states')->willReturn(['Core' => false]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('System module cannot be enabled manually: Core');

        (new ModuleActivationManager($modules, $installations))->enable('Core');
    }

    /**
     * @param array<string, ModuleDefinition> $definitions
     */
    private function modules(array $definitions): ModuleManager
    {
        $modules = $this->createStub(ModuleManager::class);
        $modules->method('availableDefinitions')->willReturn($definitions);

        return $modules;
    }
}
