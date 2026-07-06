<?php

namespace Codemonster\Support\Tests\Helpers;

use Codemonster\Http\Request;
use PHPUnit\Framework\TestCase;

class RequestHelperTest extends TestCase
{
    protected function setUp(): void
    {
        app()->singleton('request', fn () => Request::capture());
    }

    public function testRequestReturnsInstance(): void
    {
        $this->assertInstanceOf(Request::class, request());
    }

    public function testRequestReturnsValue(): void
    {
        $_GET['key'] = 'value';

        app()->singleton('request', fn () => \Codemonster\Http\Request::capture());

        $this->assertSame('value', request('key'));
    }

    public function testRequestReturnsEmptyAndZeroValues(): void
    {
        $_GET['empty'] = '';
        $_GET['zero'] = '0';

        app()->singleton('request', fn () => \Codemonster\Http\Request::capture());

        $this->assertSame('', request('empty'));
        $this->assertSame('0', request('zero'));
    }
}
