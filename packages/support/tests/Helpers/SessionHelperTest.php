<?php

namespace Tests\Helpers;

use PHPUnit\Framework\TestCase;
use Codemonster\Session\Session;

class SessionHelperTest extends TestCase
{
    protected function setUp(): void
    {
        app()->singleton('session', fn() => new Session());
    }

    public function testSessionReturnsInstance()
    {
        $this->assertInstanceOf(Session::class, session());
    }

    public function testSessionCanStoreAndRetrieveValues()
    {
        session('user', 'Vasya');

        $this->assertSame('Vasya', session('user'));
    }

    public function testSessionCanStoreNullValue()
    {
        session('nullable', null);

        $this->assertNull(session('nullable'));
    }
}
