<?php

namespace Codemonster\Support\Tests\Helpers;

use PHPUnit\Framework\TestCase;

class ConfigHelperTest extends TestCase
{
    protected function setUp(): void
    {
        $config = new \Codemonster\Config\Config();
        $config->set('app.name', 'Codemonster');
        $config->set('app.debug', true);

        app()->singleton('config', fn () => $config);
    }

    public function testGetsConfigValue()
    {
        $this->assertSame('Codemonster', config('app.name'));
    }

    public function testSetsConfigValue()
    {
        config(['app.env' => 'local']);

        $this->assertSame('local', config('app.env'));
    }

    public function testReturnsAllWhenNoKey()
    {
        $this->assertIsArray(config());
    }
}
