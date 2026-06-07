<?php

namespace Codemonster\Annabel\Tests\Http;

use Codemonster\Annabel\Http\Kernel;
use Codemonster\Annabel\Http\Exceptions\MethodNotAllowedHttpException;
use Codemonster\Http\Request;
use Codemonster\Annabel\Application;
use Codemonster\Router\Router;
use Codemonster\Http\Response;
use Codemonster\Annabel\Validation\ValidationException;
use Codemonster\Annabel\Validation\ValidationResult;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\AbstractLogger;
use PHPUnit\Framework\TestCase;

class TestMiddlewareA
{
    public function handle(Request $req, callable $next, $role = null): mixed
    {
        $out = $next($req);

        return 'A(' . $out . ')';
    }
}

class TestMiddlewareB
{
    public function handle(Request $req, callable $next, $role = null): mixed
    {
        $out = $next($req);

        return 'B(' . $out . ')';
    }
}

class TestMiddlewareRole
{
    public function handle(Request $req, callable $next, $role = null): mixed
    {
        $out = $next($req);

        return $role . ':' . $out;
    }
}

class TestMissingController {}

class TestPsrMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $handler->handle($request)->withHeader('X-PSR-15', 'yes');
    }
}

class TestArrayLogger extends AbstractLogger
{
    public array $records = [];

    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $this->records[] = compact('level', 'message', 'context');
    }
}

class TestValidatingController
{
    use \Codemonster\Annabel\Http\ValidatesRequests;

    public function store(Request $request): array
    {
        return $this->validate($request, [
            'email' => 'required|email',
        ]);
    }
}

class KernelTest extends TestCase
{
    public function test_kernel_dispatches_route()
    {
        Application::resetInstance();

        $router = new Router();
        $router->get('/hello', fn() => 'world');
        $app = new Application(__DIR__ . '/..');

        $kernel = new Kernel($app, $router);
        $req = new Request('GET', '/hello');
        $res = $kernel->handle($req);

        $this->assertInstanceOf(Response::class, $res);
        $this->assertEquals('world', $res->getContent());
    }

    public function test_kernel_returns_404()
    {
        Application::resetInstance();

        $router = new Router();
        $app = new Application(__DIR__ . '/..');
        $kernel = new Kernel($app, $router);

        $res = $kernel->handle(new Request('GET', '/not-found'));

        $this->assertEquals(404, $res->getStatusCode());
    }

    public function test_kernel_runs_multiple_middleware_from_single_call()
    {
        Application::resetInstance();

        $router = new Router();
        $router->get('/hello', fn() => 'world')->middleware(TestMiddlewareA::class, TestMiddlewareB::class);
        $app = new Application(__DIR__ . '/..');
        $kernel = new Kernel($app, $router);

        $res = $kernel->handle(new Request('GET', '/hello'));

        $this->assertEquals('A(B(world))', $res->getContent());
    }

    public function test_kernel_treats_class_and_role_as_single_middleware()
    {
        Application::resetInstance();

        $router = new Router();
        $router->get('/hello', fn() => 'world')->middleware([TestMiddlewareRole::class, 'admin']);
        $app = new Application(__DIR__ . '/..');
        $kernel = new Kernel($app, $router);

        $res = $kernel->handle(new Request('GET', '/hello'));

        $this->assertEquals('admin:world', $res->getContent());
    }

    public function test_kernel_handles_missing_controller_method()
    {
        Application::resetInstance();

        $router = new Router();
        $router->get('/missing', [TestMissingController::class, 'missing']);
        $app = new Application(__DIR__ . '/..');
        $kernel = new Kernel($app, $router);

        $res = $kernel->handle(new Request('GET', '/missing'));

        $this->assertEquals(500, $res->getStatusCode());
    }

    public function test_kernel_runs_psr15_middleware()
    {
        Application::resetInstance();

        $router = new Router();
        $router->get('/hello', fn() => 'world')->middleware(TestPsrMiddleware::class);
        $app = new Application(__DIR__ . '/..');
        $kernel = new Kernel($app, $router);

        $res = $kernel->handle(new Request('GET', '/hello'));

        $this->assertEquals('world', $res->getContent());
        $this->assertSame(['yes'], $res->getHeader('X-PSR-15'));
    }

    public function test_kernel_reports_exceptions_to_psr_logger()
    {
        Application::resetInstance();

        $logger = new TestArrayLogger();
        $router = new Router();
        $router->get('/boom', fn() => throw new \RuntimeException('Boom'));
        $app = new Application(__DIR__ . '/..');
        $app->getContainer()->instance(\Psr\Log\LoggerInterface::class, $logger);
        $kernel = new Kernel($app, $router);

        $res = $kernel->handle(new Request('GET', '/boom'));

        $this->assertEquals(500, $res->getStatusCode());
        $this->assertSame('error', $logger->records[0]['level']);
        $this->assertSame('Boom', $logger->records[0]['message']);
        $this->assertInstanceOf(\RuntimeException::class, $logger->records[0]['context']['exception']);
    }

    public function test_kernel_returns_json_422_for_validation_exceptions()
    {
        Application::resetInstance();

        $router = new Router();
        $router->post('/users', fn() => throw new ValidationException(new ValidationResult([
            'email' => ['The email field is required.'],
        ], [])));
        $app = new Application(__DIR__ . '/..');
        $kernel = new Kernel($app, $router);

        $res = $kernel->handle(new Request('POST', '/users', headers: [
            'Accept' => 'application/json',
        ]));

        $this->assertEquals(422, $res->getStatusCode());
        $this->assertTrue($res->isJson());
        $this->assertStringContainsString('The email field is required.', $res->getContent());
    }

    public function test_kernel_redirects_back_for_web_validation_exceptions()
    {
        Application::resetInstance();

        $router = new Router();
        $router->post('/users', fn() => throw new ValidationException(new ValidationResult([
            'email' => ['The email field is required.'],
        ], [])));
        $app = new Application(__DIR__ . '/..');
        $kernel = new Kernel($app, $router);

        $res = $kernel->handle(new Request('POST', '/users', body: [
            'name' => 'Annabel',
        ], headers: [
            'Referer' => '/users/create',
        ]));

        $this->assertEquals(302, $res->getStatusCode());
        $this->assertSame(['/users/create'], $res->getHeader('Location'));
        $this->assertSame(
            ['email' => ['The email field is required.']],
            $app->make('session')->get('errors')
        );
        $this->assertSame(['name' => 'Annabel'], $app->make('session')->get('_old_input'));
    }

    public function test_kernel_rejects_external_validation_redirects()
    {
        Application::resetInstance();

        $router = new Router();
        $router->post('/users', fn() => throw new ValidationException(new ValidationResult([
            'email' => ['The email field is required.'],
        ], [])));
        $app = new Application(__DIR__ . '/..');
        $kernel = new Kernel($app, $router);

        $res = $kernel->handle(new Request('POST', '/users', headers: [
            'Referer' => 'https://attacker.example/collect',
        ], server: [
            'HTTP_HOST' => 'annabel.test',
        ]));

        $this->assertEquals(422, $res->getStatusCode());
        $this->assertSame([], $res->getHeader('Location'));
    }

    public function test_kernel_normalizes_same_origin_validation_redirects()
    {
        Application::resetInstance();

        $router = new Router();
        $router->post('/users', fn() => throw new ValidationException(new ValidationResult([
            'email' => ['The email field is required.'],
        ], [])));
        $app = new Application(__DIR__ . '/..');
        $kernel = new Kernel($app, $router);

        $res = $kernel->handle(new Request('POST', '/users', headers: [
            'Referer' => 'http://annabel.test/users/create?plan=pro',
        ], server: [
            'HTTP_HOST' => 'annabel.test',
        ]));

        $this->assertEquals(302, $res->getStatusCode());
        $this->assertSame(['/users/create?plan=pro'], $res->getHeader('Location'));
    }

    public function test_kernel_excludes_sensitive_fields_from_old_input()
    {
        Application::resetInstance();

        $router = new Router();
        $router->post('/users', fn() => throw new ValidationException(new ValidationResult([
            'email' => ['The email field is required.'],
        ], [])));
        $app = new Application(__DIR__ . '/..');
        $kernel = new Kernel($app, $router);

        $kernel->handle(new Request('POST', '/users', body: [
            'name' => 'Annabel',
            'password' => 'secret',
            'profile' => [
                'api_key' => 'private',
                'city' => 'Novokuznetsk',
            ],
        ], headers: [
            'Referer' => '/users/create',
        ]));

        $this->assertSame([
            'name' => 'Annabel',
            'profile' => ['city' => 'Novokuznetsk'],
        ], $app->make('session')->get('_old_input'));
    }

    public function test_kernel_preserves_http_exception_headers()
    {
        Application::resetInstance();

        $router = new Router();
        $router->post('/users', fn() => throw new MethodNotAllowedHttpException(['GET', 'HEAD']));
        $app = new Application(__DIR__ . '/..');
        $kernel = new Kernel($app, $router);

        $res = $kernel->handle(new Request('POST', '/users'));

        $this->assertEquals(405, $res->getStatusCode());
        $this->assertSame(['GET, HEAD'], $res->getHeader('Allow'));
    }

    public function test_kernel_returns_json_for_http_exceptions_in_api_requests()
    {
        Application::resetInstance();

        $router = new Router();
        $router->post('/users', fn() => throw new MethodNotAllowedHttpException(['GET']));
        $app = new Application(__DIR__ . '/..');
        $kernel = new Kernel($app, $router);

        $res = $kernel->handle(new Request('POST', '/users', headers: [
            'Accept' => 'application/json',
        ]));

        $this->assertEquals(405, $res->getStatusCode());
        $this->assertTrue($res->isJson());
        $this->assertSame(['GET'], $res->getHeader('Allow'));
        $this->assertStringContainsString('"status": 405', $res->getContent());
    }

    public function test_validates_requests_trait_uses_request_input()
    {
        Application::resetInstance();

        $router = new Router();
        $router->post('/users', [TestValidatingController::class, 'store']);
        $app = new Application(__DIR__ . '/..');
        $kernel = new Kernel($app, $router);

        $res = $kernel->handle(new Request('POST', '/users', body: [
            'email' => 'hello@example.com',
        ], headers: [
            'Accept' => 'application/json',
        ]));

        $this->assertEquals(200, $res->getStatusCode());
        $this->assertTrue($res->isJson());
        $this->assertStringContainsString('hello@example.com', $res->getContent());
    }
}
