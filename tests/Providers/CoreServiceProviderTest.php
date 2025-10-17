<?php

use Codemonster\Annabel\Application;
use Codemonster\Config\Config;
use Codemonster\Env\Env;
use Codemonster\Router\Router;
use PHPUnit\Framework\TestCase;

class CoreServiceProviderTest extends TestCase
{
    public function test_core_services_are_bound()
    {
        $app = new Application(__DIR__ . '/../../');
        $c = $app->getContainer();

        $this->assertTrue($c->has(Config::class));
        $this->assertTrue($c->has(Env::class));
        $this->assertTrue($c->has(Router::class));
    }
}
