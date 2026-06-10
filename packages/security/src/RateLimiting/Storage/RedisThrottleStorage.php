<?php

namespace Codemonster\Security\RateLimiting\Storage;

class RedisThrottleStorage implements AtomicThrottleStorageInterface
{
    /** @var object */
    protected $client;
    protected string $prefix;

    public function __construct(object $client, string $prefix = 'throttle:')
    {
        $this->client = $client;
        $this->prefix = $prefix;
    }

    public function get(string $key): ?array
    {
        $redisKey = $this->prefix . $key;
        $value = $this->invoke('get', $redisKey);

        if (!is_string($value)) {
            return null;
        }

        $ttl = self::integerValue($this->invoke('ttl', $redisKey), 0);
        $expiresAt = $ttl > 0 ? time() + $ttl : 0;

        return [
            'attempts' => self::integerValue($value, 0),
            'expires_at' => $expiresAt,
        ];
    }

    public function put(string $key, array $value): void
    {
        $attempts = $value['attempts'];
        $expiresAt = $value['expires_at'];
        $redisKey = $this->prefix . $key;

        if ($expiresAt > 0) {
            $ttl = max(0, $expiresAt - time());
            $this->invoke('setex', $redisKey, $ttl, (string) $attempts);

            return;
        }

        $this->invoke('set', $redisKey, (string) $attempts);
    }

    public function forget(string $key): void
    {
        $this->invoke('del', $this->prefix . $key);
    }

    public function increment(string $key, int $decaySeconds, int $now): array
    {
        $redisKey = $this->prefix . $key;
        $script = <<<'LUA'
local key = KEYS[1]
local decay = tonumber(ARGV[1])
local now = tonumber(ARGV[2])
local ttl = redis.call('ttl', key)

if ttl == -2 then
    redis.call('set', key, 1, 'ex', decay)
    return {1, now + decay}
end

if ttl == -1 then
    redis.call('expire', key, decay)
    ttl = decay
end

local attempts = redis.call('incr', key)
return {attempts, now + ttl}
LUA;

        $result = $this->evalScript($script, [$redisKey], [$decaySeconds, $now]);

        if (!is_array($result) || count($result) < 2) {
            return ['attempts' => 1, 'expires_at' => $now + $decaySeconds];
        }

        return [
            'attempts' => self::integerValue($result[0], 1),
            'expires_at' => self::integerValue($result[1], $now + $decaySeconds),
        ];
    }

    /**
     * @param list<string> $keys
     * @param list<int|string> $args
     */
    protected function evalScript(string $script, array $keys, array $args): mixed
    {
        if (is_a($this->client, 'Redis')) {
            return $this->invoke('eval', $script, array_merge($keys, $args), count($keys));
        }

        if (is_a($this->client, 'Predis\\Client')) {
            return $this->invoke('eval', $script, count($keys), ...array_merge($keys, $args));
        }

        if (method_exists($this->client, 'eval')) {
            return $this->invoke('eval', $script, count($keys), ...array_merge($keys, $args));
        }

        return null;
    }

    private function invoke(string $method, mixed ...$arguments): mixed
    {
        $callable = [$this->client, $method];

        if (!is_callable($callable)) {
            throw new \InvalidArgumentException("Redis client does not support [{$method}].");
        }

        return $callable(...$arguments);
    }

    private static function integerValue(mixed $value, int $default): int
    {
        if (is_int($value)) {
            return $value;
        }
        if (is_string($value) && preg_match('/\A-?\d+\z/', $value) === 1) {
            return (int) $value;
        }

        return $default;
    }
}
