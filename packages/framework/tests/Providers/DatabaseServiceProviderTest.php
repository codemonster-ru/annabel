<?php

namespace Codemonster\Annabel\Tests\Providers;

use Codemonster\Annabel\Application;
use Codemonster\Annabel\Providers\CoreServiceProvider;
use Codemonster\Annabel\Providers\DatabaseServiceProvider;
use Codemonster\Config\Config;
use Codemonster\Database\Contracts\ConnectionInterface;
use Codemonster\Database\ORM\Model;
use PHPUnit\Framework\TestCase;

class DatabaseServiceProviderTest extends TestCase
{
    protected function setUp(): void
    {
        Application::resetInstance();
        Config::reset();
    }

    protected function tearDown(): void
    {
        Application::resetInstance();
        Config::reset();
    }

    public function test_it_registers_model_connection_resolver(): void
    {
        $app = new Application(__DIR__ . '/../..', null, false);
        $core = new CoreServiceProvider($app);
        $core->register();

        $provider = new DatabaseServiceProvider($app);
        $provider->register();

        self::assertInstanceOf(ConnectionInterface::class, TestDatabaseModel::connectionForTest());
    }
}

class TestDatabaseModel extends Model
{
    public static function connectionForTest(): ConnectionInterface
    {
        return self::connection();
    }
}
