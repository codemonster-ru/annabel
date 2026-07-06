<?php

namespace Codemonster\Support\Tests\Helpers;

use PHPUnit\Framework\TestCase;

class EnvHelperTest extends TestCase
{
    protected function setUp(): void
    {
        putenv('APP_NAME=Codemonster');

        $_ENV['APP_NAME'] = 'Codemonster';
    }

    public function testEnvReadsVariable(): void
    {
        $this->assertSame('Codemonster', env('APP_NAME'));
    }

    public function testEnvReturnsDefaultWhenMissing(): void
    {
        $this->assertSame('default', env('MISSING_KEY', 'default'));
    }
}
