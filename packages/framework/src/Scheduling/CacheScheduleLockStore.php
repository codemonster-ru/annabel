<?php

namespace Codemonster\Annabel\Scheduling;

use Codemonster\Cache\Contracts\CacheStoreInterface;
use Codemonster\Scheduler\Contracts\LockStoreInterface;

class CacheScheduleLockStore implements LockStoreInterface
{
    public function __construct(protected CacheStoreInterface $cache)
    {
    }

    public function acquire(string $name, int $seconds): bool
    {
        return $this->cache->add($name, true, $seconds);
    }

    public function release(string $name): void
    {
        $this->cache->delete($name);
    }
}
