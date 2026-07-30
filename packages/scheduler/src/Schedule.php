<?php

namespace Codemonster\Scheduler;

use Codemonster\DateTime\SystemClock;
use Codemonster\Scheduler\Contracts\LockStoreInterface;
use Psr\Clock\ClockInterface;

class Schedule
{
    /** @var list<ScheduledTask> */
    protected array $tasks = [];

    protected ClockInterface $clock;

    public function __construct(
        protected ?LockStoreInterface $lockStore = null,
        ?ClockInterface $clock = null,
    ) {
        $this->lockStore ??= new ArrayLockStore();
        $this->clock = $clock ?? new SystemClock();
    }

    /**
     * @param callable():mixed $callback
     */
    public function call(callable $callback, string $description = 'Closure'): ScheduledTask
    {
        $task = new ScheduledTask(\Closure::fromCallable($callback), $description, $this->clock);
        $this->tasks[] = $task;

        return $task;
    }

    /**
     * @return list<ScheduledTask>
     */
    public function tasks(): array
    {
        return $this->tasks;
    }

    /**
     * @return list<ScheduledTask>
     */
    public function dueTasks(?\DateTimeInterface $now = null): array
    {
        $now ??= $this->clock->now();

        return array_values(array_filter(
            $this->tasks,
            static fn (ScheduledTask $task): bool => $task->isDue($now),
        ));
    }

    /**
     * @return list<TaskResult>
     */
    public function runDue(?\DateTimeInterface $now = null): array
    {
        return array_map(
            fn (ScheduledTask $task): TaskResult => $task->run($this->lockStore),
            $this->dueTasks($now),
        );
    }
}
