<?php

namespace Codemonster\Scheduler\Tests;

use Codemonster\Scheduler\Contracts\LockStoreInterface;
use Codemonster\Scheduler\Schedule;
use Codemonster\Scheduler\ScheduleException;
use PHPUnit\Framework\TestCase;

class SchedulerTest extends TestCase
{
    public function test_runs_due_tasks(): void
    {
        $runs = 0;
        $schedule = new Schedule();
        $schedule->call(function () use (&$runs): void {
            $runs++;
        }, 'tick')->everyFiveMinutes();

        $results = $schedule->runDue(new \DateTimeImmutable('2026-06-09 10:15:00'));

        self::assertSame(1, $runs);
        self::assertCount(1, $results);
        self::assertSame('succeeded', $results[0]->status());
        self::assertSame('tick', $results[0]->description());
    }

    public function test_skips_tasks_that_are_not_due(): void
    {
        $runs = 0;
        $schedule = new Schedule();
        $schedule->call(function () use (&$runs): void {
            $runs++;
        })->hourly();

        $results = $schedule->runDue(new \DateTimeImmutable('2026-06-09 10:15:00'));

        self::assertSame(0, $runs);
        self::assertSame([], $results);
    }

    public function test_daily_time_and_weekday_filters(): void
    {
        $schedule = new Schedule();
        $task = $schedule->call(fn (): null => null)
            ->dailyAt('09:30')
            ->weekdays();

        self::assertTrue($task->isDue(new \DateTimeImmutable('2026-06-09 09:30:00')));
        self::assertFalse($task->isDue(new \DateTimeImmutable('2026-06-09 09:31:00')));
        self::assertFalse($task->isDue(new \DateTimeImmutable('2026-06-07 09:30:00')));
    }

    public function test_failures_are_reported_without_stopping_schedule(): void
    {
        $schedule = new Schedule();
        $schedule->call(fn () => throw new \RuntimeException('Broken'), 'broken')->everyMinute();

        $results = $schedule->runDue(new \DateTimeImmutable('2026-06-09 10:15:00'));

        self::assertCount(1, $results);
        self::assertSame('failed', $results[0]->status());
        self::assertInstanceOf(\RuntimeException::class, $results[0]->exception());
    }

    public function test_without_overlapping_skips_locked_task(): void
    {
        $runs = 0;
        $lockStore = new FixedLockStore(false);
        $schedule = new Schedule($lockStore);
        $schedule->call(function () use (&$runs): void {
            $runs++;
        }, 'locked-task')->withoutOverlapping();

        $results = $schedule->runDue(new \DateTimeImmutable('2026-06-09 10:15:00'));

        self::assertSame(0, $runs);
        self::assertCount(1, $results);
        self::assertSame('skipped', $results[0]->status());
        self::assertSame(['schedule.lock.' . sha1('locked-task')], $lockStore->acquired);
    }

    public function test_without_overlapping_releases_lock_after_run(): void
    {
        $lockStore = new FixedLockStore(true);
        $schedule = new Schedule($lockStore);
        $schedule->call(fn (): null => null, 'cleanup')->withoutOverlapping(5, 'custom-lock');

        $results = $schedule->runDue(new \DateTimeImmutable('2026-06-09 10:15:00'));

        self::assertSame('succeeded', $results[0]->status());
        self::assertSame(['schedule.lock.' . sha1('custom-lock')], $lockStore->released);
        self::assertSame([300], $lockStore->seconds);
    }

    public function test_invalid_daily_time_is_rejected(): void
    {
        $this->expectException(ScheduleException::class);

        (new Schedule())->call(fn (): null => null)->dailyAt('25:00');
    }
}

class FixedLockStore implements LockStoreInterface
{
    /** @var list<string> */
    public array $acquired = [];
    /** @var list<string> */
    public array $released = [];
    /** @var list<int> */
    public array $seconds = [];

    public function __construct(private bool $shouldAcquire)
    {
    }

    public function acquire(string $name, int $seconds): bool
    {
        $this->acquired[] = $name;
        $this->seconds[] = $seconds;

        return $this->shouldAcquire;
    }

    public function release(string $name): void
    {
        $this->released[] = $name;
    }
}
