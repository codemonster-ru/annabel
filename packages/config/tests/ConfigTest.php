<?php

declare(strict_types=1);

use Codemonster\Config\Config;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    protected string $configPath;

    protected function setUp(): void
    {
        $this->configPath = sys_get_temp_dir() . '/codemonster_config_test';

        if (!is_dir($this->configPath)) {
            mkdir($this->configPath, 0777, true);
        }

        file_put_contents($this->configPath . '/app.php', <<<PHP
        <?php
        return [
            'name' => 'Codemonster',
            'debug' => false,
        ];
        PHP);

        file_put_contents($this->configPath . '/database.php', <<<PHP
        <?php
        return [
            'host' => 'localhost',
            'port' => 3306,
        ];
        PHP);

        Config::reset();
        Config::load($this->configPath);
    }

    public function test_can_load_config_files(): void
    {
        $this->assertEquals('Codemonster', Config::get('app.name'));
        $this->assertEquals(3306, Config::get('database.port'));
    }

    public function test_can_get_value_with_default(): void
    {
        $this->assertEquals('default', Config::get('nonexistent.key', 'default'));
    }

    public function test_can_set_new_value(): void
    {
        Config::set('app.debug', true);

        $this->assertTrue(Config::get('app.debug'));
    }

    public function test_can_get_all_configs(): void
    {
        $all = Config::all();

        $this->assertArrayHasKey('app', $all);
        $this->assertArrayHasKey('database', $all);
        $app = $all['app'];
        $this->assertIsArray($app);
        $this->assertEquals('Codemonster', $app['name'] ?? null);
    }

    public function test_can_load_cached_config(): void
    {
        $cache = $this->configPath . '/cached.php';
        file_put_contents($cache, "<?php\nreturn ['app' => ['name' => 'Cached']];\n");

        Config::loadCached($cache);

        $this->assertSame('Cached', Config::get('app.name'));
        $this->assertNull(Config::get('database.host'));

        unlink($cache);
    }
}
