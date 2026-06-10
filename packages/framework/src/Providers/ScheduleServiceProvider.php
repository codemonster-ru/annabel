<?php

namespace Codemonster\Annabel\Providers;

use Codemonster\Annabel\Container;
use Codemonster\Annabel\Scheduling\CacheScheduleLockStore;
use Codemonster\Cache\Contracts\CacheStoreInterface;
use Codemonster\Scheduler\ArrayLockStore;
use Codemonster\Scheduler\Contracts\LockStoreInterface;
use Codemonster\Scheduler\Schedule;

class ScheduleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->publishes([
            __DIR__ . '/../../routes/schedule.php' => $this->app()->getBasePath() . '/routes/schedule.php',
        ], ['routes', 'schedule']);

        $this->app()->singleton(LockStoreInterface::class, function (Container $app): LockStoreInterface {
            if ($app->has(CacheStoreInterface::class)) {
                return new CacheScheduleLockStore($app->make(CacheStoreInterface::class));
            }

            return new ArrayLockStore();
        });

        $this->app()->singleton(Schedule::class, function (Container $app): Schedule {
            $schedule = new Schedule($app->make(LockStoreInterface::class));
            $this->loadSchedule($schedule);

            return $schedule;
        });
        $this->app()->singleton('schedule', fn (Container $app): Schedule => $app->make(Schedule::class));
    }

    private function loadSchedule(Schedule $schedule): void
    {
        $file = $this->app()->getBasePath() . '/routes/schedule.php';

        if (!is_file($file)) {
            return;
        }

        require $file;
    }
}
