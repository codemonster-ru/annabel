<?php

use Codemonster\Annabel\Application;
use PHPUnit\Framework\TestCase;

class AppHelperTest extends TestCase
{
    public function test_app_returns_instance()
    {
        $this->assertInstanceOf(Application::class, app());
    }
}
