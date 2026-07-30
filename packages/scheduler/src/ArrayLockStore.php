<?php

namespace Codemonster\Scheduler;

use Codemonster\DateTime\SystemClock;
use Codemonster\Scheduler\Contracts\LockStoreInterface;
use Psr\Clock\ClockInterface;

class ArrayLockStore implements LockStoreInterface
{
    /** @var array<string, int> */
    protected array $locks = [];
    protected ClockInterface $clock;

    public function __construct(?ClockInterface $clock = null)
    {
        $this->clock = $clock ?? new SystemClock();
    }

    public function acquire(string $name, int $seconds): bool
    {
        $now = $this->clock->now()->getTimestamp();

        if (isset($this->locks[$name]) && $this->locks[$name] > $now) {
            return false;
        }

        $this->locks[$name] = $now + max(1, $seconds);

        return true;
    }

    public function release(string $name): void
    {
        unset($this->locks[$name]);
    }
}
