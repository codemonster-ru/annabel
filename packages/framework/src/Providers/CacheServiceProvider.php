<?php

namespace Codemonster\Annabel\Providers;

use Codemonster\Annabel\Container;
use Codemonster\Cache\CacheManager;
use Codemonster\Cache\Contracts\CacheStoreInterface;
use Psr\SimpleCache\CacheInterface;

class CacheServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/cache.php' => $this->app()->getBasePath() . '/config/cache.php',
        ], ['config', 'cache']);

        $this->app()->singleton(CacheManager::class, fn (): CacheManager => new CacheManager(
            $this->cacheConfig(),
        ));
        $this->app()->singleton('cache.manager', fn (Container $app): CacheManager => $app->make(CacheManager::class));
        $this->app()->singleton(CacheStoreInterface::class, fn (Container $app): CacheStoreInterface => $app
            ->make(CacheManager::class)
            ->store());
        $this->app()->singleton(CacheInterface::class, fn (Container $app): CacheInterface => $app->make(CacheStoreInterface::class));
        $this->app()->singleton('cache', fn (Container $app): CacheInterface => $app->make(CacheInterface::class));
    }

    /**
     * @return array<string, mixed>
     */
    private function cacheConfig(): array
    {
        $config = config('cache', [
            'default' => 'array',
            'stores' => [
                'array' => [
                    'driver' => 'array',
                ],
            ],
        ]);

        if (!is_array($config)) {
            throw new \RuntimeException('Cache config must be an array.');
        }

        $normalized = [];
        foreach ($config as $key => $value) {
            if (is_string($key)) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }
}
