<?php

namespace Codemonster\Annabel\Tests\Testing;

use Codemonster\Annabel\Application;
use Codemonster\Annabel\Http\Kernel;
use Codemonster\Annabel\Testing\InteractsWithApplication;
use Codemonster\Http\Response;
use Codemonster\Router\Router;
use PHPUnit\Framework\TestCase;

class TestResponseTest extends TestCase
{
    use InteractsWithApplication;

    protected function tearDown(): void
    {
        Application::resetInstance();
    }

    public function test_response_assertions(): void
    {
        $this->get('/hello')
            ->assertOk()
            ->assertJson(['message' => 'Hello'])
            ->assertJsonPath('meta.version', 1);
    }

    public function test_json_requests(): void
    {
        $this->json('POST', '/echo', ['name' => 'Annabel'])
            ->assertOk()
            ->assertJsonPath('name', 'Annabel');
    }

    public function test_redirect_assertion(): void
    {
        $this->get('/redirect')->assertRedirect('/target');
    }

    protected function createApplication(): Application
    {
        $router = new Router();
        $router->get('/hello', fn () => Response::json([
            'message' => 'Hello',
            'meta' => ['version' => 1],
        ]));
        $router->post('/echo', fn (\Codemonster\Http\Request $request) => Response::json($request->input()));
        $router->get('/redirect', fn () => Response::redirect('/target'));

        $app = new Application(__DIR__ . '/..', null, false);
        $app->setKernel(new Kernel($app, $router));

        return $app;
    }
}
