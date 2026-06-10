<?php

namespace Codemonster\Scheduler;

use Codemonster\Scheduler\Contracts\LockStoreInterface;

class ArrayLockStore implements LockStoreInterface
{
    /** @var array<string, int> */
    protected array $locks = [];

    public function acquire(string $name, int $seconds): bool
    {
        $now = time();

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
