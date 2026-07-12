<?php

namespace Codemonster\Cms\Tests\Integration;

use Codemonster\Annabel\Application;
use Codemonster\Cms\Modules\Auth\Contracts\AuthenticatedUser;
use Codemonster\Cms\Modules\Auth\Contracts\AuthenticatorInterface;
use Codemonster\Cms\Modules\Auth\Contracts\UserSessionInterface;
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

    private function app(): Application
    {
        $app = require dirname(__DIR__, 2) . '/bootstrap/app.php';
        $app->getContainer()->instance(InstallationState::class, new InstalledInstallationState());

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
