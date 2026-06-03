<?php

namespace Codemonster\Session;

use Codemonster\Session\Handlers\ArraySessionHandler;
use Codemonster\Session\Handlers\FileSessionHandler;
use SessionHandlerInterface;

class Session
{
    protected static ?Store $store = null;

    /**
     * @param array<string, mixed> $options
     */
    public static function start(
        string $driver = 'file',
        array $options = [],
        ?SessionHandlerInterface $customHandler = null
    ): void {
        if ($customHandler) {
            $handler = $customHandler;
        } elseif ($driver === 'array') {
            $handler = new ArraySessionHandler();
        } else {
            $savePath = $options['path'] ?? sys_get_temp_dir() . '/sessions';
            $handler = new FileSessionHandler($savePath);
        }

        $cookieOptions = $options['cookie'] ?? [];
        $encryptionOptions = $options['encryption'] ?? [];
        static::$store = new Store($handler, null, $cookieOptions, $encryptionOptions);
        static::$store->start();

        if (!empty($options['regenerate'])) {
            static::$store->regenerateId(true);
        }
    }

    public static function store(): Store
    {
        if (!static::$store) {
            static::start();
        }

        return static::$store;
    }

    public static function manager(): SessionManager
    {
        return new SessionManager(static::store());
    }

    public static function regenerate(bool $destroyOld = true): void
    {
        static::store()->regenerateId($destroyOld);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return static::store()->get($key, $default);
    }

    public static function has(string $key): bool
    {
        return static::store()->has($key);
    }

    /**
     * @return array<int, string>
     */
    public static function keys(?string $prefix = null): array
    {
        return static::store()->keys($prefix);
    }

    /**
     * @return array<int, string>
     */
    public static function keysMatch(string $pattern): array
    {
        return static::store()->keysMatch($pattern);
    }

    public static function count(): int
    {
        return static::store()->count();
    }

    public static function size(): int
    {
        return static::store()->size();
    }

    public static function put(string $key, mixed $value): void
    {
        static::store()->put($key, $value);
    }

    public static function putWithTtl(string $key, mixed $value, int $ttlSeconds): void
    {
        static::store()->putWithTtl($key, $value, $ttlSeconds);
    }

    public static function ttl(string $key): ?int
    {
        return static::store()->ttl($key);
    }

    public static function expiresAt(string $key): ?int
    {
        return static::store()->expiresAt($key);
    }

    public static function touch(string $key, int $ttlSeconds): bool
    {
        return static::store()->touch($key, $ttlSeconds);
    }

    public static function touchAll(int $ttlSeconds, ?string $prefix = null): int
    {
        return static::store()->touchAll($ttlSeconds, $prefix);
    }

    /**
     * @param array<int, string> $redactKeys
     * @param array<int, string> $redactPatterns
     * @return array<string, mixed>
     */
    public static function dump(array $redactKeys = [], array $redactPatterns = []): array
    {
        return static::store()->dump($redactKeys, $redactPatterns);
    }

    public static function forget(string $key): void
    {
        static::store()->forget($key);
    }

    /**
     * @param array<int, string> $keys
     */
    public static function forgetMany(array $keys): void
    {
        static::store()->forgetMany($keys);
    }

    public static function pull(string $key, mixed $default = null): mixed
    {
        return static::store()->pull($key, $default);
    }

    public static function increment(string $key, int $by = 1): int
    {
        return static::store()->increment($key, $by);
    }

    public static function sweepExpired(): void
    {
        static::store()->sweepExpired();
    }

    /**
     * @param array<int, string> $previousKeys
     */
    public static function rotateEncryptionKey(
        string $newKey,
        array $previousKeys = [],
        bool $allowPlaintext = false
    ): void {
        static::store()->rotateEncryptionKey($newKey, $previousKeys, $allowPlaintext);
    }

    public static function for(string $namespace, string $delimiter = '.'): SessionScope
    {
        return new SessionScope(static::store(), $namespace, $delimiter);
    }

    public static function flash(string $key, mixed $value): void
    {
        static::store()->flash($key, $value);
    }

    /**
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        return static::store()->all();
    }

    public static function destroy(bool $clearCookie = true): void
    {
        if (!static::$store) {
            return;
        }

        static::$store->destroy($clearCookie);
    }

    public static function forgetNamespace(string $prefix): void
    {
        static::store()->forgetNamespace($prefix);
    }
}
