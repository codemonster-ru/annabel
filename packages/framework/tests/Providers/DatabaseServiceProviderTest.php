<?php

namespace Codemonster\Annabel\Tests\Providers;

use Codemonster\Annabel\Application;
use Codemonster\Annabel\Providers\CoreServiceProvider;
use Codemonster\Annabel\Providers\DatabaseServiceProvider;
use Codemonster\Config\Config;
use Codemonster\Database\Contracts\ConnectionInterface;
use Codemonster\Database\ORM\Model;
use Codemonster\DateTime\FrozenClock;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

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

    public function test_it_registers_the_application_clock_for_models(): void
    {
        $app = new Application(__DIR__ . '/../..', null, false);
        (new CoreServiceProvider($app))->register();
        $clock = new FrozenClock(new \DateTimeImmutable('2026-06-09 10:15:00 UTC'));
        $app->getContainer()->instance(ClockInterface::class, $clock);

        (new DatabaseServiceProvider($app))->register();

        self::assertSame('2026-06-09 10:15:00', (new TestDatabaseModel())->freshTimestampForTest());
    }
}

class TestDatabaseModel extends Model
{
    public static function connectionForTest(): ConnectionInterface
    {
        return self::connection();
    }

    public function freshTimestampForTest(): string
    {
        return $this->freshTimestamp();
    }
}
