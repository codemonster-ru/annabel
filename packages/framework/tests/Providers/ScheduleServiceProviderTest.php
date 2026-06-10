<?php

namespace Codemonster\Annabel\Tests\Providers;

use Codemonster\Annabel\Application;
use Codemonster\Annabel\Providers\CoreServiceProvider;
use Codemonster\Annabel\Providers\ScheduleServiceProvider;
use Codemonster\Annabel\Publishing\PublishRegistry;
use Codemonster\Annabel\Scheduling\CacheScheduleLockStore;
use Codemonster\Cache\ArrayCache;
use Codemonster\Cache\Contracts\CacheStoreInterface;
use Codemonster\Scheduler\Contracts\LockStoreInterface;
use Codemonster\Scheduler\Schedule;
use PHPUnit\Framework\TestCase;

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
