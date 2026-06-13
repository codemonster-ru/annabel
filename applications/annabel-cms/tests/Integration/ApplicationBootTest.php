<?php

namespace Codemonster\Cms\Tests\Integration;

use Codemonster\Annabel\Application;
use Codemonster\Cms\Modules\Auth\Contracts\AuthenticatorInterface;
use Codemonster\Cms\Modules\Auth\Contracts\UserSessionInterface;
use Codemonster\Cms\Modules\Core\ModuleManager;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class ApplicationBootTest extends TestCase
{
    public function testModulesBootInDependencyOrderAndContractsResolve(): void
    {
        $app = $this->app();
        $modules = $app->make(ModuleManager::class);

        self::assertSame([
            'Core' => '1.0.0',
            'Auth' => '1.0.0',
            'Admin' => '1.0.0',
            'Pages' => '1.0.0',
        ], $modules->listAll());
        self::assertInstanceOf(AuthenticatorInterface::class, $app->make(AuthenticatorInterface::class));
        self::assertInstanceOf(UserSessionInterface::class, $app->make(UserSessionInterface::class));
    }

    public function testLeafModuleCanBeDisabled(): void
    {
        $this->environment('ANNABEL_CMS_DISABLED_MODULES', 'Pages');

        $modules = $this->app()->make(ModuleManager::class);

        self::assertArrayNotHasKey('Pages', $modules->listAll());
    }

    public function testRequiredModuleCannotBeDisabled(): void
    {
        $this->environment('ANNABEL_CMS_DISABLED_MODULES', 'Auth');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Required module is disabled or missing: Auth');

        $this->app();
    }

    private function app(): Application
    {
        return require dirname(__DIR__, 2) . '/bootstrap/app.php';
    }

    private function environment(string $key, string $value): void
    {
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
        putenv("{$key}={$value}");
    }
}
