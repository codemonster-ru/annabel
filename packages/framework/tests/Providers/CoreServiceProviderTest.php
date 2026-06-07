<?php

namespace Codemonster\Annabel\Tests\Providers;


use Codemonster\Annabel\Application;
use Codemonster\Config\Config;
use Codemonster\Env\Env;
use Codemonster\Router\Router;
use Codemonster\Annabel\Validation\Validator;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\SimpleCache\CacheInterface;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

class CoreServiceProviderTest extends TestCase
{
    public function test_core_services_are_bound()
    {
        Application::resetInstance();

        $app = new Application(__DIR__ . '/../../');
        $c = $app->getContainer();

        $this->assertTrue($c->has(Config::class));
        $this->assertTrue($c->has(Env::class));
        $this->assertTrue($c->has(Router::class));
        $this->assertInstanceOf(LoggerInterface::class, $c->make(LoggerInterface::class));
        $this->assertInstanceOf(CacheInterface::class, $c->make(CacheInterface::class));
        $this->assertInstanceOf(ListenerProviderInterface::class, $c->make(ListenerProviderInterface::class));
        $this->assertInstanceOf(EventDispatcherInterface::class, $c->make(EventDispatcherInterface::class));
        $this->assertInstanceOf(Validator::class, $c->make(Validator::class));
    }
}
