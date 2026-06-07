<?php

use Psr\SimpleCache\CacheInterface;

if (!function_exists('cache')) {
    function cache(?string $key = null, mixed $value = null, \DateInterval|int|null $ttl = null): mixed
    {
        /** @var CacheInterface $cache */
        $cache = app(CacheInterface::class);

        if ($key === null) {
            return $cache;
        }

        if (func_num_args() === 1) {
            return $cache->get($key);
        }

        $cache->set($key, $value, $ttl);

        return $cache;
    }
}
