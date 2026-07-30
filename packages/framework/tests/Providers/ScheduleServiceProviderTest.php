<?php

namespace Codemonster\Annabel\Tests\Providers;

use Codemonster\Annabel\Application;
use Codemonster\Annabel\Providers\CoreServiceProvider;
use Codemonster\Annabel\Providers\ScheduleServiceProvider;
use Codemonster\Annabel\Publishing\PublishRegistry;
use Codemonster\Annabel\Scheduling\CacheScheduleLockStore;
use Codemonster\Cache\ArrayCache;
use Codemonster\Cache\Contracts\CacheStoreInterface;
use Codemonster\DateTime\FrozenClock;
use Codemonster\Scheduler\ArrayLockStore;
use Codemonster\Scheduler\Contracts\LockStoreInterface;
use Codemonster\Scheduler\Schedule;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

class ScheduleServiceProviderTest extends TestCase
{
    protected function tearDown(): void
    {
        Application::resetInstance();
    }

    public function test_schedule_service_is_registered(): void
    {
        $app = $this->app();

        self::assertInstanceOf(Schedule::class, $app->make(Schedule::class));
        self::assertInstanceOf(Schedule::class, $app->make('schedule'));
    }

    public function test_schedule_uses_cache_lock_store_when_cache_is_registered(): void
    {
        $app = $this->app();
        $app->singleton(CacheStoreInterface::class, fn (): CacheStoreInterface => new ArrayCache());

        self::assertInstanceOf(CacheScheduleLockStore::class, $app->make(LockStoreInterface::class));
    }

    public function test_schedule_uses_the_registered_clock(): void
    {
        $app = $this->app();
        $clock = new FrozenClock(new \DateTimeImmutable('2026-06-09 10:15:00 UTC'));
        $app->getContainer()->instance(ClockInterface::class, $clock);
        $schedule = $app->make(Schedule::class);
        $schedule->call(fn (): null => null)->everyFiveMinutes();

        self::assertCount(1, $schedule->dueTasks());
    }

    public function test_array_lock_store_uses_the_registered_clock(): void
    {
        $app = $this->app();
        $clock = new FrozenClock(new \DateTimeImmutable('2026-06-09 10:15:00 UTC'));
        $app->getContainer()->instance(ClockInterface::class, $clock);
        $lockStore = $app->make(LockStoreInterface::class);

        self::assertInstanceOf(ArrayLockStore::class, $lockStore);

        $property = new \ReflectionProperty(ArrayLockStore::class, 'clock');

        self::assertSame($clock, $property->getValue($lockStore));
    }

    public function test_schedule_routes_are_publishable(): void
    {
        $app = $this->app();

        /** @var PublishRegistry $registry */
        $registry = $app->make(PublishRegistry::class);
        $resources = $registry->matching(ScheduleServiceProvider::class, 'schedule');

        self::assertCount(1, $resources);
        self::assertSame($app->getBasePath() . '/routes/schedule.php', $resources[0]['destination']);
        self::assertFileExists($resources[0]['source']);
    }

    private function app(): Application
    {
        Application::resetInstance();

        $app = new Application(__DIR__ . '/../..', null, false);
        (new CoreServiceProvider($app))->register();
        (new ScheduleServiceProvider($app))->register();

        return $app;
    }
}
