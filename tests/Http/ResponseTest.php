<?php

use Codemonster\Annabel\Http\Response;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    public function test_basic_response()
    {
        $r = new Response('ok', 201, ['X-Test' => '1']);

        $this->assertEquals('ok', $r->getContent());
        $this->assertEquals(201, $r->getStatusCode());
        $this->assertArrayHasKey('X-Test', $r->getHeaders());
    }

    public function test_json_factory()
    {
        $r = Response::json(['a' => 1]);

        $this->assertTrue($r->isJson());
        $this->assertStringContainsString('"a": 1', $r->getContent());
    }

    public function test_redirect_factory()
    {
        $r = Response::redirect('/login');

        $this->assertEquals('/login', $r->getHeaders()['Location']);
    }
}
