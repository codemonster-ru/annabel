<?php

namespace Annabel\Tests;

use Annabel\Application;
use Annabel\Http\Request;
use PHPUnit\Framework\TestCase;

class ApplicationTest extends TestCase
{
    public function testCanRegisterAndResolveRoute(): void
    {
        $app = new Application();
        $app->get('/hello', fn() => 'Hi!');

        $request = new Request('GET', '/hello');
        $response = $app->handle($request);

        $this->assertEquals('Hi!', $this->getOutput($response));
    }

    public function testReturns404ForUnknownRoute(): void
    {
        $app = new Application();

        $request = new Request('GET', '/unknown');
        $response = $app->handle($request);

        $this->assertEquals('Not Found', $this->getOutput($response));
    }

    private function getOutput($response): string
    {
        ob_start();
        $response->send();
        return ob_get_clean();
    }
}
