<?php

namespace Codemonster\Support\Tests\Helpers;

use Codemonster\Session\Session;
use PHPUnit\Framework\TestCase;

class ValidationHelperTest extends TestCase
{
    protected function setUp(): void
    {
        app()->singleton('session', fn () => new Session());
    }

    public function test_old_returns_flashed_input()
    {
        session('_old_input', [
            'name' => 'Annabel',
            'user' => ['email' => 'hello@example.com'],
        ]);

        $this->assertSame('Annabel', old('name'));
        $this->assertSame('hello@example.com', old('user.email'));
        $this->assertSame('fallback', old('missing', 'fallback'));
    }

    public function test_errors_returns_validation_errors()
    {
        session('errors', [
            'email' => ['The email field is required.'],
        ]);

        $this->assertSame(['email' => ['The email field is required.']], errors());
        $this->assertSame(['The email field is required.'], errors('email'));
        $this->assertNull(errors('name'));
    }
}
