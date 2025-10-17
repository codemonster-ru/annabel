<?php

use Codemonster\Config\Config;
use PHPUnit\Framework\TestCase;

class ConfigHelperTest extends TestCase
{
    public function test_config_get_and_set()
    {
        $cfg = app(Config::class);
        $cfg->set('foo.bar', 123);

        $this->assertEquals(123, config('foo.bar'));
    }
}
