<?php

namespace Codemonster\Cms\Tests\Integration;

use Codemonster\Annabel\Application;
use Codemonster\Cms\Modules\Admin\Contracts\AdminScreenRendererInterface;
use Codemonster\Cms\Modules\Admin\Services\AdminNavigationRegistry;
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
        $adminNavigation = $app->make(AdminNavigationRegistry::class);

        self::assertSame([
            'Core' => '1.0.0',
            'Auth' => '1.0.0',
            'Admin' => '1.0.0',
            'AdminUsers' => '1.0.0',
            'Pages' => '1.0.0',
            'Setup' => '1.0.0',
        ], $modules->listAll());

        self::assertSame([
            [
                'value' => 'dashboard',
                'label' => 'Dashboard',
                'href' => '/admin',
            ],
            [
                'value' => 'settings',
                'label' => 'Settings',
                'children' => [
                    [
                        'value' => 'admin.users',
                        'label' => 'Users',
                        'children' => [
                            [
                                'value' => 'admin.users.list',
                                'label' => 'User list',
                                'href' => '/admin/settings/users',
                            ],
                        ],
                    ],
                ],
            ],
        ], $adminNavigation->navigation());
        self::assertSame('User list', $adminNavigation->label('admin.users.list'));
        self::assertNull($adminNavigation->label('admin.missing'));

        $basePath = dirname(__DIR__, 2);
        self::assertSame([
            $basePath . '/app/Modules/Auth/database/migrations',
            $basePath . '/app/Modules/Pages/database/migrations',
        ], $modules->migrationPaths());
        self::assertSame([
            $basePath . '/app/Modules/Auth/database/seeds',
            $basePath . '/app/Modules/Pages/database/seeds',
        ], $modules->seedPaths());
        self::assertSame($modules->migrationPaths(), config('database.migrations.paths'));
        self::assertSame($modules->seedPaths(), config('database.seeds.paths'));

        self::assertInstanceOf(AuthenticatorInterface::class, $app->make(AuthenticatorInterface::class));
        self::assertInstanceOf(UserSessionInterface::class, $app->make(UserSessionInterface::class));
        self::assertInstanceOf(AdminScreenRendererInterface::class, $app->make(AdminScreenRendererInterface::class));
    }

    private function app(): Application
    {
        return require dirname(__DIR__, 2) . '/bootstrap/app.php';
    }
}
