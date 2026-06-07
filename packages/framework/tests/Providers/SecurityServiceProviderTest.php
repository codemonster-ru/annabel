<?php

use Codemonster\Annabel\Application;
use Codemonster\Annabel\Http\Kernel;
use Codemonster\Annabel\Providers\SecurityServiceProvider;
use Codemonster\Security\Csrf\CsrfTokenManager;
use Codemonster\Security\Csrf\VerifyCsrfToken;
use Codemonster\Security\RateLimiting\Contracts\RateLimiterInterface;
use Codemonster\Security\RateLimiting\ThrottleRequests;
use PHPUnit\Framework\TestCase;

class SecurityServiceProviderTest extends TestCase
{
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

        $reflection = new ReflectionClass(Kernel::class);
        $property = $reflection->getProperty('middleware');
        $property->setAccessible(true);

        $this->assertNotEmpty($property->getValue($app->getKernel()));
    }
}
