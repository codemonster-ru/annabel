<?php

namespace Codemonster\Annabel\Tests\Providers;

use Codemonster\Annabel\Application;
use Codemonster\Annabel\Http\Kernel;
use Codemonster\Annabel\Providers\AuthServiceProvider;
use Codemonster\Annabel\Providers\CoreServiceProvider;
use Codemonster\Annabel\Providers\SessionServiceProvider;
use Codemonster\Annabel\Publishing\PublishRegistry;
use Codemonster\Auth\Contracts\AuthenticatableInterface;
use Codemonster\Auth\Contracts\GuardInterface;
use Codemonster\Auth\Contracts\PasswordHasherInterface;
use Codemonster\Auth\Contracts\UserProviderInterface;
use Codemonster\Auth\Database\DatabaseUserProvider;
use Codemonster\Auth\Guards\SessionGuard;
use Codemonster\Auth\Hashing\NativePasswordHasher;
use Codemonster\Auth\Middleware\Authenticate;
use Codemonster\Auth\Middleware\Authorize;
use Codemonster\Config\Config;
use Codemonster\Database\Connection;
use Codemonster\Database\Contracts\ConnectionInterface;
use PHPUnit\Framework\TestCase;

class AuthServiceProviderTest extends TestCase
{
    protected function tearDown(): void
    {
        Application::resetInstance();
        Config::reset();
    }

    public function test_auth_services_are_registered(): void
    {
        $hasher = new NativePasswordHasher();
        $user = new AuthTestUser(7, 'admin@example.com', $hasher->make('secret'));
        $app = $this->app([
            'auth.users' => [$user],
            'session.driver' => 'array',
        ]);

        $guard = $app->make('auth');

        self::assertInstanceOf(GuardInterface::class, $guard);
        self::assertInstanceOf(SessionGuard::class, $guard);
        self::assertInstanceOf(PasswordHasherInterface::class, $app->make('auth.hasher'));
        self::assertInstanceOf(UserProviderInterface::class, $app->make('auth.provider'));

        self::assertTrue($guard->attempt([
            'email' => 'admin@example.com',
            'password' => 'secret',
        ]));
        self::assertSame(7, $guard->id());
    }

    public function test_configured_provider_class_is_validated(): void
    {
        $app = $this->app([
            'auth.provider' => \stdClass::class,
            'session.driver' => 'array',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Configured auth provider');

        $app->make(UserProviderInterface::class);
    }

    public function test_authenticate_middleware_uses_configured_redirect(): void
    {
        $app = $this->app([
            'auth.redirect_to' => '/login',
            'session.driver' => 'array',
        ]);

        self::assertInstanceOf(Authenticate::class, $app->make(Authenticate::class));
        self::assertInstanceOf(Authorize::class, $app->make(Authorize::class));
        self::assertSame([
            'auth' => Authenticate::class,
            'can' => Authorize::class,
        ], $app->make(Kernel::class)->getMiddlewareAliases());
    }

    public function test_database_provider_can_be_selected_by_config(): void
    {
        $app = $this->app([
            'auth.provider' => 'database',
            'session.driver' => 'array',
        ]);
        $app->getContainer()->instance(ConnectionInterface::class, new Connection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]));

        self::assertInstanceOf(DatabaseUserProvider::class, $app->make(UserProviderInterface::class));
    }

    public function test_auth_config_is_publishable(): void
    {
        $app = $this->app([
            'session.driver' => 'array',
        ]);

        /** @var PublishRegistry $registry */
        $registry = $app->make(PublishRegistry::class);
        $resources = $registry->matching(AuthServiceProvider::class, 'auth');

        self::assertCount(1, $resources);
        self::assertSame($app->getBasePath() . '/config/auth.php', $resources[0]['destination']);
        self::assertFileExists($resources[0]['source']);
    }

    /**
     * @param array<string, mixed> $configuration
     */
    private function app(array $configuration): Application
    {
        Application::resetInstance();

        $app = new Application(__DIR__ . '/../..', null, false);
        (new CoreServiceProvider($app))->register();

        config($configuration);

        (new SessionServiceProvider($app))->register();
        $authProvider = new AuthServiceProvider($app);
        $authProvider->register();
        $authProvider->boot();

        return $app;
    }
}

class AuthTestUser implements AuthenticatableInterface
{
    public function __construct(
        private int $id,
        private string $email,
        private string $password,
    ) {
    }

    public function getAuthIdentifier(): int
    {
        return $this->id;
    }

    public function getAuthPassword(): string
    {
        return $this->password;
    }

    public function email(): string
    {
        return $this->email;
    }
}
