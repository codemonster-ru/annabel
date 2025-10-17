<?php

use Codemonster\Annabel\Application;
use Codemonster\View\View;
use PHPUnit\Framework\TestCase;

class ViewServiceProviderTest extends TestCase
{
    public function test_view_service_is_registered()
    {
        $app = new Application(__DIR__ . '/../../');

        $this->assertTrue($app->getContainer()->has(View::class));
        $this->assertInstanceOf(View::class, $app->getView());
    }
}
