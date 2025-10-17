<?php

use Codemonster\Annabel\Http\Request;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    public function test_method_and_uri()
    {
        $r = new Request('GET', '/home');

        $this->assertEquals('GET', $r->method());
        $this->assertEquals('/home', $r->uri());
    }

    public function test_capture_reads_globals()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/contact';
        $_POST = ['name' => 'Kirill'];

        $r = Request::capture();
        $this->assertEquals('POST', $r->method());
        $this->assertEquals('Kirill', $r->input('name'));
    }
}
