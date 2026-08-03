<?php

namespace Codemonster\Cms\Tests\Integration;

use Codemonster\Annabel\Application;
use Codemonster\Cms\Modules\Admin\Contracts\AdminScreenRendererInterface;
use Codemonster\Cms\Modules\Admin\Services\AdminNavigationRegistry;
use Codemonster\Cms\Modules\Auth\Contracts\AuthenticatorInterface;
use Codemonster\Cms\Modules\Auth\Contracts\UserSessionInterface;
use Codemonster\Cms\Modules\Core\ModuleManager;
use Codemonster\Cms\Modules\Settings\Services\SiteSettings;
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
            'Settings' => '1.0.0',
            'Admin' => '1.0.0',
            'Pages' => '1.0.0',
            'AdminPages' => '1.0.0',
            'AdminSettings' => '1.0.0',
            'AdminUsers' => '1.0.0',
            'Setup' => '1.0.0',
        ], $modules->listAll());

        self::assertSame([
            [
                'value' => 'dashboard',
                'label' => 'Dashboard',
                'leadingIcon' => 'house',
                'href' => '/admin',
            ],
            [
                'value' => 'admin.users',
                'label' => 'Users',
                'leadingIcon' => 'users',
                'children' => [
                    [
                        'value' => 'admin.users.list',
                        'label' => 'User list',
                        'href' => '/admin/users',
                    ],
                    [
                        'value' => 'admin.users.groups',
                        'label' => 'Groups',
                        'href' => '/admin/groups',
                    ],
                ],
            ],
            [
                'value' => 'admin.pages',
                'label' => 'Pages',
                'leadingIcon' => 'file-text',
                'href' => '/admin/pages',
            ],
            [
                'value' => 'settings',
                'label' => 'Settings',
                'leadingIcon' => 'gear',
                'children' => [
                    [
                        'value' => 'admin.settings.general',
                        'label' => 'General',
                        'href' => '/admin/settings/general',
                    ],
                ],
            ],
        ], $adminNavigation->navigation());
        self::assertSame('General', $adminNavigation->label('admin.settings.general'));
        self::assertSame('User list', $adminNavigation->label('admin.users.list'));
        self::assertSame('Groups', $adminNavigation->label('admin.users.groups'));
        self::assertNull($adminNavigation->label('admin.missing'));

        $basePath = dirname(__DIR__, 2);
        self::assertSame([
            $basePath . '/app/Modules/Core/database/migrations',
            $basePath . '/app/Modules/Auth/database/migrations',
            $basePath . '/app/Modules/Settings/database/migrations',
            $basePath . '/app/Modules/Pages/database/migrations',
            $basePath . '/app/Modules/AdminPages/database/migrations',
        ], $modules->migrationPaths());
        self::assertSame([
            $basePath . '/app/Modules/Auth/database/seeds',
            $basePath . '/app/Modules/Settings/database/seeds',
            $basePath . '/app/Modules/Pages/database/seeds',
        ], $modules->seedPaths());
        self::assertSame($modules->migrationPaths(), config('database.migrations.paths'));
        self::assertSame($modules->seedPaths(), config('database.seeds.paths'));

        self::assertInstanceOf(AuthenticatorInterface::class, $app->make(AuthenticatorInterface::class));
        self::assertInstanceOf(UserSessionInterface::class, $app->make(UserSessionInterface::class));
        self::assertInstanceOf(AdminScreenRendererInterface::class, $app->make(AdminScreenRendererInterface::class));
        self::assertInstanceOf(SiteSettings::class, $app->make(SiteSettings::class));
    }

    private function app(): Application
    {
        return require dirname(__DIR__, 2) . '/bootstrap/app.php';
    }
}
