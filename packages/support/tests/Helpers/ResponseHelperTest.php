<?php

namespace Tests\Helpers;

use PHPUnit\Framework\TestCase;
use Codemonster\Http\Response;

class ResponseHelperTest extends TestCase
{
    protected function setUp(): void
    {
        app()->singleton('response', fn() => new Response());
    }

    public function testResponseReturnsInstance()
    {
        $this->assertInstanceOf(Response::class, response());
    }

    public function testResponseSetsContent()
    {
        $res = response('Hello', 200);

        $this->assertInstanceOf(Response::class, $res);
        $this->assertSame('Hello', $res->getContent());
    }

    public function testJsonResponse()
    {
        $res = json(['ok' => true]);

        $this->assertInstanceOf(Response::class, $res);
        $this->assertJson($res->getContent());
        $this->assertJsonStringEqualsJsonString('{"ok":true}', $res->getContent());
    }

    public function testAbortThrowsHttpLikeException()
    {
        $this->expectException(\RuntimeException::class);

        try {
            abort(403, 'Forbidden');
        } catch (\Throwable $e) {
            /** @var \Codemonster\Support\Contracts\HttpStatusExceptionInterface $e */

            $this->assertSame(403, $e->getStatusCode());
            $this->assertSame('Forbidden', $e->getMessage());

            throw $e;
        }
    }

    public function testAbortGeneratesDefaultMessage()
    {
        $this->expectException(\RuntimeException::class);

        try {
            abort(404);
        } catch (\Throwable $e) {
            /** @var \Codemonster\Support\Contracts\HttpStatusExceptionInterface $e */

            $this->assertSame('HTTP 404', $e->getMessage());
            $this->assertSame(404, $e->getStatusCode());

            throw $e;
        }
    }
}
