<?php

use Codemonster\Http\Request;
use PHPUnit\Framework\TestCase;

class RequestHelperTest extends TestCase
{
    public function test_request_returns_instance()
    {
        $this->assertInstanceOf(Request::class, request());
    }
}
