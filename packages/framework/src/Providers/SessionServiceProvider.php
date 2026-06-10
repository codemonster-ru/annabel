<?php

namespace Codemonster\Annabel\Providers;

use Codemonster\Annabel\Contracts\ServiceProviderInterface;
use Codemonster\Session\Handlers\RedisSessionHandler;
use Codemonster\Session\Session;
use Codemonster\Session\Store;

class SessionServiceProvider extends ServiceProvider implements ServiceProviderInterface
{
    public function register(): void
    {
        $this->app()->singleton('session', fn () => $this->startSession());
    }

    public function boot(): void
    {
        $this->app()->make('session');
    }

    private function startSession(): Store
    {
        $driver = $this->stringConfig('session.driver', 'file');
        $path = $this->stringConfig(
            'session.path',
            $this->app()->getBasePath() . '/storage/sessions',
        );
        $options = [
            'path' => $path,
            'cookie' => $this->arrayConfig('session.cookie'),
            'encryption' => $this->arrayConfig('session.encryption'),
        ];

        if ($driver === 'redis') {
            Session::start(
                options: $options,
                customHandler: $this->redisHandler(),
            );

            return Session::store();
        }

        if ($driver === 'array') {
            Session::start('array', $options);

            return Session::store();
        }

        if ($driver !== 'file') {
            throw new \InvalidArgumentException("Unsupported session driver: {$driver}");
        }

        $this->ensureWritableDirectory($path);
        Session::start('file', $options);

        return Session::store();
    }

    private function ensureWritableDirectory(string $path): void
    {
        if ($path === '') {
            throw new \RuntimeException('The session path cannot be empty.');
        }

        if (!is_dir($path) && !mkdir($path, 0770, true) && !is_dir($path)) {
            throw new \RuntimeException("Unable to create the session directory: {$path}");
        }

        if (!is_writable($path)) {
            throw new \RuntimeException(
                "The session directory is not writable by the PHP process: {$path}",
            );
        }
    }

    private function redisHandler(): RedisSessionHandler
    {
        if (!class_exists(\Redis::class)) {
            throw new \RuntimeException(
                'The redis session driver requires the PHP Redis extension.',
            );
        }

        $redis = new \Redis();
        $connected = $redis->connect(
            $this->stringConfig('session.redis.host', '127.0.0.1'),
            $this->intConfig('session.redis.port', 6379),
            $this->floatConfig('session.redis.timeout', 2.0),
        );

        if (!$connected) {
            throw new \RuntimeException('Unable to connect to the session Redis server.');
        }

        $password = config('session.redis.password');

        if (is_string($password) && $password !== '' && !$redis->auth($password)) {
            throw new \RuntimeException('Unable to authenticate with the session Redis server.');
        }

        $database = $this->intConfig('session.redis.database', 0);

        if ($database !== 0 && !$redis->select($database)) {
            throw new \RuntimeException('Unable to select the session Redis database.');
        }

        return new RedisSessionHandler(
            $redis,
            $this->stringConfig('session.redis.prefix', 'session:'),
            $this->intConfig('session.redis.ttl', 86400),
        );
    }

    private function stringConfig(string $key, string $default): string
    {
        $value = config($key, $default);

        return is_string($value) ? $value : $default;
    }

    private function intConfig(string $key, int $default): int
    {
        $value = config($key, $default);

        return is_int($value) ? $value : $default;
    }

    private function floatConfig(string $key, float $default): float
    {
        $value = config($key, $default);

        return is_float($value) || is_int($value) ? (float) $value : $default;
    }

    /** @return array<string, mixed> */
    private function arrayConfig(string $key): array
    {
        $value = config($key, []);
        if (!is_array($value)) {
            return [];
        }

        $result = [];
        foreach ($value as $name => $item) {
            if (is_string($name)) {
                $result[$name] = $item;
            }
        }

        return $result;
    }
}
