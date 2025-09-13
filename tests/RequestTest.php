<?php

namespace Annabel\Tests;

use Annabel\Http\Request;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    public function testRequestStoresMethodAndUri(): void
    {
        $request = new Request('POST', '/users');
        $this->assertEquals('POST', $request->method());
        $this->assertEquals('/users', $request->uri());
    }
}
