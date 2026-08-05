<?php

namespace Codemonster\Cms\Tests\Integration;

use Codemonster\Annabel\Application;
use Codemonster\Cms\Modules\Auth\Contracts\AuthenticatedUser;
use Codemonster\Cms\Modules\Auth\Contracts\AuthenticatorInterface;
use Codemonster\Cms\Modules\Auth\Contracts\UserSessionInterface;
use Codemonster\Cms\Modules\Core\Services\ModuleActivationManager;
use Codemonster\Cms\Modules\Core\Services\ModuleInstallationRegistry;
use Codemonster\Cms\Modules\Core\Services\ModuleInstaller;
use Codemonster\Cms\Modules\Core\Services\ModuleUpdater;
use Codemonster\Cms\Modules\Settings\Models\SiteSetting;
use Codemonster\Cms\Modules\Settings\Services\SiteSettings;
use Codemonster\Cms\Support\Installation\InstallationState;
use Codemonster\Http\Request;
use Codemonster\Security\Csrf\CsrfTokenManager;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class AdminHttpTest extends TestCase
{
    public function testGuestReceivesVueShell(): void
    {
        $response = $this->app()->handle(new Request('GET', '/admin/login'));

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('id="admin-app"', (string) $response->getContent());
        self::assertMatchesRegularExpression(
            '#/admin/assets/admin-[^"]+\.js#',
            (string) $response->getContent(),
        );
    }

    public function testAdminLoginRequiresCsrfToken(): void
    {
        $response = $this->app()->handle(new Request(
            'POST',
            '/admin/login',
            [],
            ['login' => 'admin@example.com', 'password' => 'secret'],
            ['Accept' => 'application/json'],
        ));

        self::assertSame(419, $response->getStatusCode());
        self::assertSame(
            'CSRF token mismatch.',
            json_decode((string) $response->getContent(), true)['message'],
        );
    }

    public function testAdminCanLoginThroughContracts(): void
    {
        $app = $this->app();
        $identity = new AuthenticatedUser(1, 'admin', 'admin@example.com', ['admin']);
        $session = new InMemoryUserSession();

        $app->getContainer()->instance(
            AuthenticatorInterface::class,
            new FixedAuthenticator($identity),
        );
        $app->getContainer()->instance(UserSessionInterface::class, $session);

        $token = $app->make(CsrfTokenManager::class)->token();
        $response = $app->handle(new Request(
            'POST',
            '/admin/login',
            [],
            [
                '_token' => $token,
                'login' => 'admin@example.com',
                'password' => 'secret',
            ],
            ['Accept' => 'application/json'],
        ));
        $payload = json_decode((string) $response->getContent(), true);

        self::assertSame(200, $response->getStatusCode());
        self::assertTrue($payload['authenticated']);
        self::assertSame('admin@example.com', $payload['user']['email']);
        self::assertSame($identity, $session->current());
    }

    public function testNonAdminCannotLoginToAdmin(): void
    {
        $app = $this->app();
        $identity = new AuthenticatedUser(2, 'user', 'user@example.com', ['user']);

        $app->getContainer()->instance(
            AuthenticatorInterface::class,
            new FixedAuthenticator($identity),
        );
        $app->getContainer()->instance(UserSessionInterface::class, new InMemoryUserSession());

        $token = $app->make(CsrfTokenManager::class)->token();
        $response = $app->handle(new Request(
            'POST',
            '/admin/login',
            [],
            [
                '_token' => $token,
                'login' => 'user@example.com',
                'password' => 'secret',
            ],
            ['Accept' => 'application/json'],
        ));

        self::assertSame(403, $response->getStatusCode());
    }

    public function testUserWithAdminAccessPermissionCanLogin(): void
    {
        $app = $this->app();
        $identity = new AuthenticatedUser(2, 'editor', 'editor@example.com', ['editor'], ['admin.access', 'pages.view']);

        $app->getContainer()->instance(
            AuthenticatorInterface::class,
            new FixedAuthenticator($identity),
        );
        $app->getContainer()->instance(UserSessionInterface::class, new InMemoryUserSession());

        $token = $app->make(CsrfTokenManager::class)->token();
        $response = $app->handle(new Request(
            'POST',
            '/admin/login',
            [],
            [
                '_token' => $token,
                'login' => 'editor@example.com',
                'password' => 'secret',
            ],
            ['Accept' => 'application/json'],
        ));

        self::assertSame(200, $response->getStatusCode());
    }

    public function testPermissionLimitsAdminRoutesAndNavigation(): void
    {
        $app = $this->app();
        $session = new InMemoryUserSession();
        $session->login(new AuthenticatedUser(
            2,
            'editor',
            'editor@example.com',
            ['editor'],
            ['admin.access', 'pages.view'],
        ));
        $app->getContainer()->instance(UserSessionInterface::class, $session);

        $pages = $app->handle(new Request('GET', '/admin/pages'));
        $users = $app->handle(new Request(
            'GET',
            '/admin/users',
            [],
            [],
            ['Accept' => 'application/json'],
        ));

        self::assertSame(200, $pages->getStatusCode());
        self::assertStringContainsString('/admin/pages', (string) $pages->getContent());
        self::assertStringNotContainsString('/admin/users', (string) $pages->getContent());
        self::assertSame(403, $users->getStatusCode());
    }

    public function testOwnPagePermissionReachesObjectPolicy(): void
    {
        $app = $this->app();
        $session = new InMemoryUserSession();
        $session->login(new AuthenticatedUser(
            2,
            'editor',
            'editor@example.com',
            ['editor'],
            ['admin.access', 'pages.view', 'pages.update.own'],
        ));
        $app->getContainer()->instance(UserSessionInterface::class, $session);

        $response = $app->handle(new Request('GET', '/admin/pages/999999/edit'));

        self::assertSame(404, $response->getStatusCode());
    }

    public function testGuestCannotCallAdminLogout(): void
    {
        $app = $this->app();
        $token = $app->make(CsrfTokenManager::class)->token();
        $response = $app->handle(new Request(
            'POST',
            '/admin/logout',
            [],
            ['_token' => $token],
            ['Accept' => 'application/json'],
        ));

        self::assertSame(401, $response->getStatusCode());
    }

    public function testAdminCanOpenUserList(): void
    {
        $app = $this->app();
        $session = new InMemoryUserSession();
        $session->login(new AuthenticatedUser(1, 'admin', 'admin@example.com', ['admin']));
        $app->getContainer()->instance(UserSessionInterface::class, $session);

        $response = $app->handle(new Request('GET', '/admin/users'));

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('"screen":"admin.users.list"', (string) $response->getContent());
        self::assertStringContainsString('"locale":"en"', (string) $response->getContent());
    }

    public function testGuestCannotLoadUserListData(): void
    {
        $response = $this->app()->handle(new Request(
            'GET',
            '/admin/users/data',
            [],
            [],
            ['Accept' => 'application/json'],
        ));

        self::assertSame(401, $response->getStatusCode());
    }

    public function testGuestCannotLoadUserRoleOptions(): void
    {
        $response = $this->app()->handle(new Request(
            'GET',
            '/admin/users/role-options',
            [],
            [],
            ['Accept' => 'application/json'],
        ));

        self::assertSame(401, $response->getStatusCode());
    }

    public function testAdminCanOpenRoleList(): void
    {
        $app = $this->app();
        $session = new InMemoryUserSession();
        $session->login(new AuthenticatedUser(1, 'admin', 'admin@example.com', ['admin']));
        $app->getContainer()->instance(UserSessionInterface::class, $session);

        $response = $app->handle(new Request('GET', '/admin/roles'));

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('"screen":"admin.roles.list"', (string) $response->getContent());
    }

    public function testGuestCannotLoadRoleListData(): void
    {
        $response = $this->app()->handle(new Request(
            'GET',
            '/admin/roles/data',
            [],
            [],
            ['Accept' => 'application/json'],
        ));

        self::assertSame(401, $response->getStatusCode());
    }

    public function testAdminCanOpenGeneralSettings(): void
    {
        $app = $this->app();
        $session = new InMemoryUserSession();
        $session->login(new AuthenticatedUser(1, 'admin', 'admin@example.com', ['admin']));
        $app->getContainer()->instance(UserSessionInterface::class, $session);

        $response = $app->handle(new Request('GET', '/admin/settings/general'));

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('"screen":"admin.settings.general"', (string) $response->getContent());
    }

    public function testGuestCannotLoadGeneralSettingsData(): void
    {
        $response = $this->app()->handle(new Request(
            'GET',
            '/admin/settings/general/data',
            [],
            [],
            ['Accept' => 'application/json'],
        ));

        self::assertSame(401, $response->getStatusCode());
    }

    public function testAdminCanOpenModuleList(): void
    {
        $app = $this->app();
        $session = new InMemoryUserSession();
        $session->login(new AuthenticatedUser(1, 'admin', 'admin@example.com', ['admin']));
        $app->getContainer()->instance(UserSessionInterface::class, $session);

        $response = $app->handle(new Request('GET', '/admin/settings/modules'));

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('"screen":"admin.settings.modules"', (string) $response->getContent());
    }

    public function testAdminCanLoadModuleListData(): void
    {
        $app = $this->app();
        $session = new InMemoryUserSession();
        $session->login(new AuthenticatedUser(1, 'admin', 'admin@example.com', ['admin']));
        $app->getContainer()->instance(UserSessionInterface::class, $session);

        $response = $app->handle(new Request(
            'GET',
            '/admin/settings/modules/data',
            ['page' => '1', 'per_page' => '10'],
            [],
            ['Accept' => 'application/json'],
        ));
        $payload = json_decode((string) $response->getContent(), true);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame([], $payload['data']);
        self::assertSame(0, $payload['total']);
        self::assertSame(1, $payload['current_page']);
        self::assertSame(10, $payload['per_page']);
    }

    public function testAdminCanOpenSystemUpdates(): void
    {
        $app = $this->app();
        $session = new InMemoryUserSession();
        $session->login(new AuthenticatedUser(1, 'admin', 'admin@example.com', ['admin']));
        $app->getContainer()->instance(UserSessionInterface::class, $session);

        $response = $app->handle(new Request('GET', '/admin/settings/system/updates'));

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString(
            '"screen":"admin.settings.system-updates"',
            (string) $response->getContent(),
        );
    }

    public function testAdminCanLoadSystemUpdateData(): void
    {
        $app = $this->app();
        $session = new InMemoryUserSession();
        $session->login(new AuthenticatedUser(1, 'admin', 'admin@example.com', ['admin']));
        $app->getContainer()->instance(UserSessionInterface::class, $session);

        $response = $app->handle(new Request(
            'GET',
            '/admin/settings/system/updates/data',
            [],
            [],
            ['Accept' => 'application/json'],
        ));
        $payload = json_decode((string) $response->getContent(), true);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('1.1.0', $payload['cms_version']);
        self::assertNull($payload['latest_version']);
        self::assertSame('stable', $payload['channel']);
        self::assertNull($payload['last_checked_at']);
        self::assertNull($payload['last_successful_update_at']);
        self::assertSame(10, $payload['total']);
        self::assertSame(1, $payload['current_page']);
        self::assertSame(10, $payload['per_page']);
        self::assertSame([
            'Admin',
            'AdminModules',
            'AdminPages',
            'AdminSettings',
            'AdminUsers',
            'Auth',
            'Core',
            'Pages',
            'Settings',
            'Setup',
        ], array_column($payload['components'], 'name'));
        self::assertSame(
            [
                'name' => 'Setup',
                'installed_version' => null,
                'available_version' => null,
            ],
            $payload['components'][9],
        );
    }

    public function testGuestCannotLoadSystemUpdateData(): void
    {
        $response = $this->app()->handle(new Request(
            'GET',
            '/admin/settings/system/updates/data',
            [],
            [],
            ['Accept' => 'application/json'],
        ));

        self::assertSame(401, $response->getStatusCode());
    }

    public function testGuestCannotLoadModuleListData(): void
    {
        $response = $this->app()->handle(new Request(
            'GET',
            '/admin/settings/modules/data',
            [],
            [],
            ['Accept' => 'application/json'],
        ));

        self::assertSame(401, $response->getStatusCode());
    }

    public function testAdminCanDisableModule(): void
    {
        $app = $this->app();
        $session = new InMemoryUserSession();
        $session->login(new AuthenticatedUser(1, 'admin', 'admin@example.com', ['admin']));
        $app->getContainer()->instance(UserSessionInterface::class, $session);

        $activation = $this->createMock(ModuleActivationManager::class);
        $activation->expects(self::once())->method('disable')->with('Demo');
        $app->getContainer()->instance(ModuleActivationManager::class, $activation);

        $response = $app->handle(new Request(
            'POST',
            '/admin/settings/modules/data/Demo/disable',
            [],
            ['_token' => $app->make(CsrfTokenManager::class)->token()],
            ['Accept' => 'application/json'],
        ));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(
            'Module disabled successfully.',
            json_decode((string) $response->getContent(), true)['message'],
        );
    }

    public function testAdminCanInstallModule(): void
    {
        $app = $this->app();
        $session = new InMemoryUserSession();
        $session->login(new AuthenticatedUser(1, 'admin', 'admin@example.com', ['admin']));
        $app->getContainer()->instance(UserSessionInterface::class, $session);

        $installer = $this->createMock(ModuleInstaller::class);
        $installer->expects(self::once())->method('install')->with('Demo');
        $app->getContainer()->instance(ModuleInstaller::class, $installer);

        $response = $app->handle(new Request(
            'POST',
            '/admin/settings/modules/data/Demo/install',
            [],
            ['_token' => $app->make(CsrfTokenManager::class)->token()],
            ['Accept' => 'application/json'],
        ));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(
            'Module installed successfully.',
            json_decode((string) $response->getContent(), true)['message'],
        );
    }

    public function testAdminCanUpdateModule(): void
    {
        $app = $this->app();
        $session = new InMemoryUserSession();
        $session->login(new AuthenticatedUser(1, 'admin', 'admin@example.com', ['admin']));
        $app->getContainer()->instance(UserSessionInterface::class, $session);

        $updater = $this->createMock(ModuleUpdater::class);
        $updater->expects(self::once())->method('update')->with('Demo');
        $app->getContainer()->instance(ModuleUpdater::class, $updater);

        $response = $app->handle(new Request(
            'POST',
            '/admin/settings/modules/data/Demo/update',
            [],
            ['_token' => $app->make(CsrfTokenManager::class)->token()],
            ['Accept' => 'application/json'],
        ));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(
            'Module updated successfully.',
            json_decode((string) $response->getContent(), true)['message'],
        );
    }

    private function app(): Application
    {
        $app = require dirname(__DIR__, 2) . '/bootstrap/app.php';
        $app->getContainer()->instance(InstallationState::class, new InstalledInstallationState());

        $settings = $this->createStub(SiteSettings::class);
        $settings->method('current')->willReturn(new SiteSetting([
            'id' => 1,
            'site_name' => 'Annabel CMS',
            'locale' => 'en',
        ], true));
        $app->getContainer()->instance(SiteSettings::class, $settings);

        $installations = $this->createStub(ModuleInstallationRegistry::class);
        $installedNames = [
            'Core',
            'Auth',
            'Settings',
            'Admin',
            'AdminModules',
            'Pages',
            'AdminPages',
            'AdminSettings',
            'AdminUsers',
        ];
        $installations->method('records')->willReturn(array_fill_keys($installedNames, [
            'is_enabled' => true,
            'installed_version' => '1.0.0',
            'installed_at' => null,
            'updated_at' => null,
        ]));
        $installations->method('states')->willReturn(array_fill_keys($installedNames, true));
        $app->getContainer()->instance(ModuleInstallationRegistry::class, $installations);

        return $app;
    }
}

final class InstalledInstallationState extends InstallationState
{
    public function __construct()
    {
        parent::__construct(sys_get_temp_dir() . '/annabel-cms-test-installed.json');
    }

    public function isInstalled(): bool
    {
        return true;
    }
}

final class FixedAuthenticator implements AuthenticatorInterface
{
    public function __construct(
        private ?AuthenticatedUser $user,
    ) {
    }

    public function attempt(string $login, string $password): ?AuthenticatedUser
    {
        return $this->user;
    }
}

final class InMemoryUserSession implements UserSessionInterface
{
    private ?AuthenticatedUser $user = null;

    public function current(bool $forceRefresh = false): ?AuthenticatedUser
    {
        return $this->user;
    }

    public function login(AuthenticatedUser $user, bool $remember = false): ?string
    {
        $this->user = $user;

        return null;
    }

    public function logout(): void
    {
        $this->user = null;
    }

    public function forgetRememberToken(int|string $userId): void
    {
    }

    public function rememberCookieName(): string
    {
        return 'annabel_remember';
    }

    public function rememberCookieLifetime(): int
    {
        return 2592000;
    }

    public function hasRole(string $role, bool $strict = false): bool
    {
        return $this->user?->hasRole($role) ?? false;
    }
}
