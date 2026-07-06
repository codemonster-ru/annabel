<?php

namespace Codemonster\Support\Tests\Helpers;

use Codemonster\Session\Session;
use PHPUnit\Framework\TestCase;

class SessionHelperTest extends TestCase
{
    protected function setUp(): void
    {
        app()->singleton('session', fn () => new Session());
    }

    public function testSessionReturnsInstance(): void
    {
        $this->assertInstanceOf(Session::class, session());
    }

    public function testSessionCanStoreAndRetrieveValues(): void
    {
        session('user', 'Vasya');

        $this->assertSame('Vasya', session('user'));
    }

    public function testSessionCanStoreNullValue(): void
    {
        session('nullable', null);

        $this->assertNull(session('nullable'));
    }
}
