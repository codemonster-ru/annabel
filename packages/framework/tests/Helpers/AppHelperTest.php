<?php

use Codemonster\Annabel\Application;
use PHPUnit\Framework\TestCase;

class AppHelperTest extends TestCase
{
    public function test_app_returns_instance()
    {
        Application::resetInstance();

        new Application(__DIR__ . '/..');

        $this->assertInstanceOf(Application::class, app());
    }

    public function test_app_resolves_with_parameters()
    {
        Application::resetInstance();

        new Application(__DIR__ . '/..');

        $subject = app(AppHelperSubject::class, ['name' => 'annabel']);

        $this->assertSame('annabel', $subject->name);
    }
}

class AppHelperSubject
{
    public function __construct(public string $name) {}
}
