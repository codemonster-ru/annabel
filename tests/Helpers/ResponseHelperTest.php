<?php

use Codemonster\Annabel\Http\Response;
use PHPUnit\Framework\TestCase;

class ResponseHelperTest extends TestCase
{
    public function test_response_creates_new_instance()
    {
        $r = response('ok');

        $this->assertInstanceOf(Response::class, $r);
        $this->assertEquals('ok', $r->getContent());
    }
}
