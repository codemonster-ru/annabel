<?php

use Codemonster\Annabel\Application;
use Codemonster\Http\Request;
use Codemonster\View\View;
use PHPUnit\Framework\TestCase;

class ApplicationTest extends TestCase
{
    public function test_bootstrap_initializes_view()
    {
        Application::resetInstance();

        $app = new Application(__DIR__ . '/..');

        $this->assertInstanceOf(View::class, $app->getView());
    }

    public function test_singleton_is_accessible()
    {
        Application::resetInstance();

        new Application(__DIR__ . '/..');

        $this->assertInstanceOf(Application::class, Application::getInstance());
    }

    public function test_routes_can_be_registered_before_bootstrap()
    {
        Application::resetInstance();

        $app = new Application(__DIR__ . '/..', null, false);
        $app->get('/hello', fn() => 'world');

        $response = $app->handle(new Request('GET', '/hello'));

        $this->assertSame('world', $response->getContent());
    }

    public function test_make_accepts_parameters()
    {
        Application::resetInstance();

        $app = new Application(__DIR__ . '/..');

        $subject = $app->make(ApplicationMakeSubject::class, ['name' => 'annabel']);

        $this->assertSame('annabel', $subject->name);
    }

    public function test_reinitialization_throws()
    {
        Application::resetInstance();

        new Application(__DIR__ . '/..');

        $this->expectException(RuntimeException::class);

        try {
            new Application(__DIR__ . '/..');
        } finally {
            Application::resetInstance();
        }
    }
}


class ApplicationMakeSubject
{
    public function __construct(public string $name) {}
}
