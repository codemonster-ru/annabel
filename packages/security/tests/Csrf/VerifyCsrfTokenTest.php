<?php

namespace Codemonster\Security\Tests\Csrf;

use Codemonster\Http\Request;
use Codemonster\Http\Response;
use Codemonster\Security\Csrf\CsrfTokenManager;
use Codemonster\Security\Csrf\VerifyCsrfToken;
use Codemonster\Session\Session;
use PHPUnit\Framework\TestCase;

class VerifyCsrfTokenTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Session::start('array');
    }

    public function testAllowsSafeMethodsAndEnsuresTokenExists(): void
    {
        $middleware = new VerifyCsrfToken();

        $request = new Request('GET', '/');

        $result = $middleware->handle($request, fn() => new Response('ok'));

        $this->assertInstanceOf(Response::class, $result);
        $this->assertSame(200, $result->getStatusCode());
        $this->assertIsString((new CsrfTokenManager())->token());
    }

    public function testValidatesTokenFromPostField(): void
    {
        $token = (new CsrfTokenManager())->token();
        $middleware = new VerifyCsrfToken();

        $request = new Request('POST', '/submit', [], ['_token' => $token], ['Accept' => 'text/html']);

        $result = $middleware->handle($request, fn() => new Response('ok'));

        $this->assertSame(200, $result->getStatusCode());
    }

    public function testValidatesTokenFromHeader(): void
    {
        $token = (new CsrfTokenManager())->token();
        $middleware = new VerifyCsrfToken();

        $request = new Request('POST', '/submit', [], [], ['X-CSRF-TOKEN' => $token, 'Accept' => 'text/html']);

        $result = $middleware->handle($request, fn() => new Response('ok'));

        $this->assertSame(200, $result->getStatusCode());
    }

    public function testRejectsInvalidTokenWithStatus419(): void
    {
        (new CsrfTokenManager())->token();

        $middleware = new VerifyCsrfToken();

        $request = new Request('POST', '/submit', [], ['_token' => 'bad'], ['Accept' => 'text/html']);

        $result = $middleware->handle($request, fn() => new Response('ok'));

        $this->assertSame(419, $result->getStatusCode());
    }

    public function testSkipsJsonRequestsByDefault(): void
    {
        (new CsrfTokenManager())->token();

        $middleware = new VerifyCsrfToken();

        $request = new Request('POST', '/api/submit', [], [], ['Accept' => 'application/json']);

        $result = $middleware->handle($request, fn() => new Response('ok'));

        $this->assertSame(200, $result->getStatusCode());
    }

    public function testSkipsExceptPaths(): void
    {
        (new CsrfTokenManager())->token();

        $middleware = new VerifyCsrfToken(null, ['api/*']);

        $request = new Request('POST', '/api/submit', [], [], ['Accept' => 'text/html']);

        $result = $middleware->handle($request, fn() => new Response('ok'));

        $this->assertSame(200, $result->getStatusCode());
    }

    public function testValidatesJsonRequestsWhenEnabled(): void
    {
        (new CsrfTokenManager())->token();

        $middleware = new VerifyCsrfToken(null, [], ['GET', 'HEAD', 'OPTIONS'], true);

        $request = new Request('POST', '/api/submit', [], [], ['Accept' => 'application/json']);

        $result = $middleware->handle($request, fn() => new Response('ok'));

        $this->assertSame(419, $result->getStatusCode());
    }
}
