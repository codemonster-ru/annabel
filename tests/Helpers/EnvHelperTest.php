<?php

use PHPUnit\Framework\TestCase;

class EnvHelperTest extends TestCase
{
    public function test_env_returns_value_or_default()
    {
        $_ENV['APP_ENV'] = 'testing';

        $this->assertEquals('testing', env('APP_ENV'));
        $this->assertEquals('default', env('MISSING', 'default'));
    }
}
