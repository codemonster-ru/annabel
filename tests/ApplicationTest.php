<?php

use Codemonster\Annabel\Application;
use Codemonster\View\View;
use PHPUnit\Framework\TestCase;

class ApplicationTest extends TestCase
{
    public function test_bootstrap_initializes_view()
    {
        $app = new Application(__DIR__ . '/..');

        $this->assertInstanceOf(View::class, $app->getView());
    }

    public function test_singleton_is_accessible()
    {
        $this->assertInstanceOf(Application::class, Application::getInstance());
    }
}
