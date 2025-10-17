<?php

use Codemonster\Router\Router;
use PHPUnit\Framework\TestCase;

class RouterHelperTest extends TestCase
{
    public function test_router_returns_instance()
    {
        $this->assertInstanceOf(Router::class, router());
    }
}
