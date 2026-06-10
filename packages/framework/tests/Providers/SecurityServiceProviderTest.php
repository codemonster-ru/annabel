<?php

namespace Codemonster\Annabel\Tests\Providers;

use Codemonster\Annabel\Application;
use Codemonster\Annabel\Http\Kernel;
use Codemonster\Annabel\Providers\SecurityServiceProvider;
use Codemonster\Annabel\Publishing\PublishRegistry;
use Codemonster\Config\Config;
use Codemonster\Http\Request;
use Codemonster\Security\Csrf\CsrfTokenManager;
use Codemonster\Security\Csrf\VerifyCsrfToken;
use Codemonster\Security\RateLimiting\Contracts\RateLimiterInterface;
use Codemonster\Security\RateLimiting\ThrottleRequests;
use PHPUnit\Framework\TestCase;

class SecurityServiceProviderTest extends TestCase
{
    protected function tearDown(): void
    {
        Config::reset();
        Application::resetInstance();
    }

    public function test_security_services_are_registered_and_middleware_is_added()
    {
        Application::resetInstance();

        $app = new Application(__DIR__ . '/../../');
        $provider = new SecurityServiceProvider($app);

        $provider->register();
        $provider->boot();

        $container = $app->getContainer();

        $this->assertTrue($container->has(CsrfTokenManager::class));
        $this->assertTrue($container->has(VerifyCsrfToken::class));
        $this->assertTrue($container->has(RateLimiterInterface::class));
        $this->assertTrue($container->has(ThrottleRequests::class));
        $aliases = $app->getKernel()->getMiddlewareAliases();
        $this->assertSame([
            'csrf' => VerifyCsrfToken::class,
            'throttle' => ThrottleRequests::class,
        ], array_intersect_key($aliases, array_flip(['csrf', 'throttle'])));
        $this->assertSame([
            'web' => ['csrf'],
            'api' => ['throttle'],
        ], array_intersect_key($app->getKernel()->getMiddlewareGroups(), array_flip(['web', 'api'])));

        $reflection = new \ReflectionClass(Kernel::class);
        $property = $reflection->getProperty('middleware');
        $property->setAccessible(true);

        $this->assertNotEmpty($property->getValue($app->getKernel()));
    }

    public function test_security_config_is_publishable(): void
    {
        Application::resetInstance();

        $app = new Application(__DIR__ . '/../../');
        $provider = new SecurityServiceProvider($app);

        $provider->register();

        /** @var PublishRegistry $registry */
        $registry = $app->make(PublishRegistry::class);
        $resources = $registry->matching(SecurityServiceProvider::class, 'security');

        $this->assertCount(1, $resources);
        $this->assertSame($app->getBasePath() . '/config/security.php', $resources[0]['destination']);
        $this->assertFileExists($resources[0]['source']);
    }

    public function test_route_throttle_alias_can_use_login_preset(): void
    {
        Application::resetInstance();
        Config::reset();

        $app = new Application(__DIR__ . '/../../');
        config([
            'security.csrf.enabled' => false,
            'security.throttle.enabled' => true,
            'security.throttle.add_to_kernel' => false,
            'security.throttle.presets' => [
                'login' => [
                    'max_attempts' => 1,
                    'decay_seconds' => 60,
                ],
            ],
        ]);

        $provider = new SecurityServiceProvider($app);
        $provider->register();
        $provider->boot();

        $app->getKernel()->getRouter()->post('/login', fn () => 'ok')->middleware('throttle:login');

        $request = new Request('POST', '/login', [], ['email' => 'user@example.com'], [], '', [
            'REMOTE_ADDR' => '203.0.113.10',
        ]);

        $this->assertSame(200, $app->getKernel()->handle($request)->getStatusCode());
        $this->assertSame(429, $app->getKernel()->handle($request)->getStatusCode());
    }
}
