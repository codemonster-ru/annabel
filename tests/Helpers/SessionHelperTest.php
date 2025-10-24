<?php

use Codemonster\Session\Session;
use PHPUnit\Framework\TestCase;

class SessionHelperTest extends TestCase
{
    protected function setUp(): void
    {
        Session::destroy();
        Session::start('array');
    }

    public function testSessionHelperSetAndGet(): void
    {
        session('name', 'Vasya');

        $this->assertSame('Vasya', session('name'));
    }

    public function testSessionHelperReturnsStore(): void
    {
        $store = session();

        $this->assertInstanceOf(\Codemonster\Session\Store::class, $store);
    }

    public function testSessionHelperUpdatesValues(): void
    {
        session('theme', 'dark');
        session('theme', 'light');

        $this->assertSame('light', session('theme'));
    }
}
