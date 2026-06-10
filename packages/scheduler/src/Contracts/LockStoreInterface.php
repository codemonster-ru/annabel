<?php

namespace Codemonster\Scheduler\Contracts;

interface LockStoreInterface
{
    public function acquire(string $name, int $seconds): bool;

    public function release(string $name): void;
}
