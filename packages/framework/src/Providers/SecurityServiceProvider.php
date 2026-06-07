<?php

namespace Codemonster\Annabel\Providers;

use Codemonster\Annabel\Application;
use Codemonster\Annabel\Container;
use Codemonster\Annabel\Http\Kernel;
use Codemonster\Http\Request;
use Codemonster\Database\DatabaseManager;
use Codemonster\Security\Csrf\CsrfTokenManager;
use Codemonster\Security\Csrf\VerifyCsrfToken;
use Codemonster\Security\RateLimiting\Contracts\RateLimiterInterface;
use Codemonster\Security\RateLimiting\RateLimiter;
use Codemonster\Security\RateLimiting\Storage\DatabaseThrottleStorage;
use Codemonster\Security\RateLimiting\Storage\RedisThrottleStorage;
use Codemonster\Security\RateLimiting\Storage\SessionThrottleStorage;
use Codemonster\Security\RateLimiting\Storage\ThrottleStorageInterface;
use Codemonster\Security\RateLimiting\ThrottleRequests;

class SecurityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app()->singleton(CsrfTokenManager::class, fn() => new CsrfTokenManager());

        $this->app()->bind(VerifyCsrfToken::class, function () {
            $cfg = $this->arrayConfig('security.csrf');

            return new VerifyCsrfToken(
                $this->app()->make(CsrfTokenManager::class),
                self::stringList($cfg['except'] ?? null),
                self::stringList($cfg['except_methods'] ?? null, ['GET', 'HEAD', 'OPTIONS']),
                is_bool($cfg['verify_json'] ?? null) ? $cfg['verify_json'] : false,
                self::stringValue($cfg['input_key'] ?? null, '_token'),
            );
        });

        $this->app()->singleton(ThrottleStorageInterface::class, function () {
            $cfg = $this->arrayConfig('security.throttle');
            $storage = self::stringValue($cfg['storage'] ?? null, 'session');

            if ($storage === 'database' && class_exists(DatabaseManager::class)) {
                $manager = $this->app()->make(DatabaseManager::class);
                $connection = is_string($cfg['connection'] ?? null) ? $cfg['connection'] : null;
                $table = self::stringValue($cfg['table'] ?? null, 'throttle_requests');

                return new DatabaseThrottleStorage($manager->connection($connection), $table);
            }

            if ($storage === 'redis') {
                $client = $cfg['redis'] ?? null;

                if (is_string($client) && class_exists($client)) {
                    $client = $this->app()->make($client);
                }

                if (is_object($client)) {
                    $prefix = self::stringValue($cfg['prefix'] ?? null, 'throttle:');

                    return new RedisThrottleStorage($client, $prefix);
                }
            }

            return new SessionThrottleStorage();
        });

        $this->app()->singleton(RateLimiterInterface::class, function () {
            return new RateLimiter($this->app()->make(ThrottleStorageInterface::class));
        });

        $this->app()->bind(ThrottleRequests::class, function () {
            $cfg = $this->arrayConfig('security.throttle');

            return new ThrottleRequests(
                $this->app()->make(RateLimiterInterface::class),
                self::positiveInt($cfg['max_attempts'] ?? null, 60),
                self::positiveInt($cfg['decay_seconds'] ?? null, 60),
                self::stringList($cfg['except'] ?? null),
                self::stringList($cfg['trusted_proxies'] ?? null),
            );
        });
    }

    public function boot(): void
    {
        $kernel = $this->app()->make(Kernel::class);
        $csrf = $this->arrayConfig('security.csrf');

        if (self::boolValue($csrf['enabled'] ?? null, true)
            && self::boolValue($csrf['add_to_kernel'] ?? null, true)) {
            $kernel->addMiddleware(function (Request $req, callable $next, Application $app) {
                return $app->make(VerifyCsrfToken::class)->handle($req, $next);
            });
        }

        $throttle = $this->arrayConfig('security.throttle');

        if (self::boolValue($throttle['enabled'] ?? null, true)
            && self::boolValue($throttle['add_to_kernel'] ?? null, false)) {
            $kernel->addMiddleware(function (Request $req, callable $next, Application $app) {
                return $app->make(ThrottleRequests::class)->handle($req, $next);
            });
        }
    }

    protected function config(string $key, mixed $default = null): mixed
    {
        if (function_exists('config')) {
            return config($key, $default);
        }

        return $default;
    }

    /** @return array<string, mixed> */
    private function arrayConfig(string $key): array
    {
        $value = $this->config($key, []);
        if (!is_array($value)) {
            return [];
        }

        $normalized = [];
        foreach ($value as $name => $item) {
            if (is_string($name)) {
                $normalized[$name] = $item;
            }
        }

        return $normalized;
    }

    /** @param list<string> $default
     *  @return list<string>
     */
    private static function stringList(mixed $value, array $default = []): array
    {
        if (!is_array($value)) {
            return $default;
        }

        $strings = array_values(array_filter($value, 'is_string'));

        return count($strings) === count($value) ? $strings : $default;
    }

    private static function stringValue(mixed $value, string $default): string
    {
        return is_string($value) ? $value : $default;
    }

    private static function positiveInt(mixed $value, int $default): int
    {
        return is_int($value) && $value > 0 ? $value : $default;
    }

    private static function boolValue(mixed $value, bool $default): bool
    {
        return is_bool($value) ? $value : $default;
    }
}
