<?php

namespace Codemonster\Cms\Tests\Integration;

use Codemonster\Annabel\Application;
use Codemonster\Cms\Modules\Auth\Contracts\AuthenticatedUser;
use Codemonster\Cms\Modules\Auth\Contracts\AuthenticatorInterface;
use Codemonster\Cms\Modules\Auth\Contracts\UserSessionInterface;
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
        $response = $this->app()->handle(new Request('GET', '/admin'));

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('id="admin-app"', (string) $response->getContent());
        self::assertMatchesRegularExpression(
            '#/admin/assets/assets/admin-[^"]+\.js#',
            (string) $response->getContent(),
        );
    }

    public function testAdminLoginRequiresCsrfToken(): void
    {
        $response = $this->app()->handle(new Request(
            'POST',
            '/admin/login',
            [],
            ['email' => 'admin@example.com', 'password' => 'secret'],
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
        $identity = new AuthenticatedUser(1, 'admin@example.com', ['admin']);
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
                'email' => 'admin@example.com',
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
        $identity = new AuthenticatedUser(2, 'user@example.com', ['user']);

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
                'email' => 'user@example.com',
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
        return require dirname(__DIR__, 2) . '/bootstrap/app.php';
    }
}

final class FixedAuthenticator implements AuthenticatorInterface
{
    public function __construct(
        private ?AuthenticatedUser $user,
    ) {
    }

    public function attempt(string $email, string $password): ?AuthenticatedUser
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

    public function login(AuthenticatedUser $user): void
    {
        $this->user = $user;
    }

    public function logout(): void
    {
        $this->user = null;
    }

    public function hasRole(string $role, bool $strict = false): bool
    {
        return $this->user?->hasRole($role) ?? false;
    }
}
