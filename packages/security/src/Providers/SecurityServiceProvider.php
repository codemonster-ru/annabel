<?php

namespace Codemonster\Security\Providers;

use Codemonster\Annabel\Contracts\ServiceProviderInterface;
use Codemonster\Annabel\Providers\ServiceProvider;
use Codemonster\Annabel\Http\Kernel;
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

class SecurityServiceProvider extends ServiceProvider implements ServiceProviderInterface
{
    public function register(): void
    {
        $this->app()->singleton(CsrfTokenManager::class, fn() => new CsrfTokenManager());

        $this->app()->bind(VerifyCsrfToken::class, function () {
            $cfg = $this->config('security.csrf', []);

            return new VerifyCsrfToken(
                $this->app()->make(CsrfTokenManager::class),
                $cfg['except'] ?? [],
                $cfg['except_methods'] ?? ['GET', 'HEAD', 'OPTIONS'],
                (bool) ($cfg['verify_json'] ?? false),
                (string) ($cfg['input_key'] ?? '_token'),
            );
        });

        $this->app()->singleton(ThrottleStorageInterface::class, function () {
            $cfg = $this->config('security.throttle', []);
            $storage = $cfg['storage'] ?? 'session';

            if ($storage === 'database' && class_exists(DatabaseManager::class)) {
                $manager = $this->app()->make(DatabaseManager::class);
                $connection = $cfg['connection'] ?? null;
                $table = (string) ($cfg['table'] ?? 'throttle_requests');

                return new DatabaseThrottleStorage($manager->connection($connection), $table);
            }

            if ($storage === 'redis') {
                $client = $cfg['redis'] ?? null;

                if (is_string($client)) {
                    $client = $this->app()->make($client);
                }

                if (is_object($client)) {
                    $prefix = (string) ($cfg['prefix'] ?? 'throttle:');

                    return new RedisThrottleStorage($client, $prefix);
                }
            }

            return new SessionThrottleStorage();
        });

        $this->app()->singleton(RateLimiterInterface::class, function () {
            return new RateLimiter($this->app()->make(ThrottleStorageInterface::class));
        });

        $this->app()->bind(ThrottleRequests::class, function () {
            $cfg = $this->config('security.throttle', []);

            return new ThrottleRequests(
                $this->app()->make(RateLimiterInterface::class),
                (int) ($cfg['max_attempts'] ?? 60),
                (int) ($cfg['decay_seconds'] ?? 60),
                $cfg['except'] ?? [],
                $cfg['trusted_proxies'] ?? [],
            );
        });
    }

    public function boot(): void
    {
        $kernel = $this->app()->make(Kernel::class);
        $csrf = $this->config('security.csrf', []);

        if (($csrf['enabled'] ?? true) && ($csrf['add_to_kernel'] ?? true)) {
            $kernel->addMiddleware(function ($req, $next, $app) {
                return $app->make(VerifyCsrfToken::class)->handle($req, $next);
            });
        }

        $throttle = $this->config('security.throttle', []);

        if (($throttle['enabled'] ?? true) && ($throttle['add_to_kernel'] ?? false)) {
            $kernel->addMiddleware(function ($req, $next, $app) {
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
}
