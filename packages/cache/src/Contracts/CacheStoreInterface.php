<?php

namespace Codemonster\Cache\Contracts;

use DateInterval;
use Psr\SimpleCache\CacheInterface;

interface CacheStoreInterface extends CacheInterface
{
    public function add(string $key, mixed $value, DateInterval|int|null $ttl = null): bool;
}
