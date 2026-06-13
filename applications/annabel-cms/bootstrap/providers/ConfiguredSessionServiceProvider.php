<?php

namespace Codemonster\Cms\Providers;

use Codemonster\Annabel\Contracts\ServiceProviderInterface;
use Codemonster\Annabel\Providers\ServiceProvider;
use Codemonster\Session\Handlers\RedisSessionHandler;
use Codemonster\Session\Session;

class ConfiguredSessionServiceProvider extends ServiceProvider implements ServiceProviderInterface
{
    public function register(): void
    {
        $driver = (string) config('session.driver', 'file');
        $path = (string) config(
            'session.path',
            $this->app()->getBasePath() . '/storage/sessions',
        );
        $options = [
            'path' => $path,
            'cookie' => (array) config('session.cookie', []),
            'encryption' => (array) config('session.encryption', []),
        ];

        if ($driver === 'redis') {
            Session::start(
                options: $options,
                customHandler: $this->redisHandler(),
            );
        } elseif ($driver === 'array') {
            Session::start('array', $options);
        } else {
            $this->ensureWritableDirectory($path);
            Session::start('file', $options);
        }

        $this->app()->getContainer()->instance('session', Session::store());
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
        if (!class_exists('Redis')) {
            throw new \RuntimeException(
                'SESSION_DRIVER=redis requires the PHP Redis extension.',
            );
        }

        $redis = new \Redis();
        $connected = $redis->connect(
            (string) config('session.redis.host', '127.0.0.1'),
            (int) config('session.redis.port', 6379),
            (float) config('session.redis.timeout', 2.0),
        );

        if (!$connected) {
            throw new \RuntimeException('Unable to connect to the session Redis server.');
        }

        $password = config('session.redis.password');

        if (is_string($password) && $password !== '' && !$redis->auth($password)) {
            throw new \RuntimeException('Unable to authenticate with the session Redis server.');
        }

        $database = (int) config('session.redis.database', 0);

        if ($database !== 0 && !$redis->select($database)) {
            throw new \RuntimeException('Unable to select the session Redis database.');
        }

        return new RedisSessionHandler(
            $redis,
            (string) config('session.redis.prefix', 'annabel_cms_session:'),
            (int) config('session.redis.ttl', 86400),
        );
    }
}
