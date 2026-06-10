<?php

namespace Codemonster\Annabel\Tests\Providers;

use Codemonster\Annabel\Application;
use Codemonster\Annabel\Providers\CacheServiceProvider;
use Codemonster\Annabel\Providers\CoreServiceProvider;
use Codemonster\Annabel\Publishing\PublishRegistry;
use Codemonster\Cache\CacheManager;
use Codemonster\Cache\Contracts\CacheStoreInterface;
use Codemonster\Config\Config;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;

class CacheServiceProviderTest extends TestCase
{
    private array $paths = [];

    protected function tearDown(): void
    {
        Application::resetInstance();
        Config::reset();

        foreach (array_reverse($this->paths) as $path) {
            if (is_file($path)) {
                @unlink($path);
            } elseif (is_dir($path)) {
                @rmdir($path);
            }
        }
    }

    public function test_cache_services_are_registered(): void
    {
        $path = $this->directory();
        $app = $this->app([
            'cache.default' => 'file',
            'cache.stores.file.driver' => 'file',
            'cache.stores.file.path' => $path,
            'cache.stores.array.driver' => 'array',
        ]);

        $cache = $app->make(CacheInterface::class);
        $cache->set('name', 'annabel');

        self::assertInstanceOf(CacheManager::class, $app->make(CacheManager::class));
        self::assertInstanceOf(CacheStoreInterface::class, $app->make(CacheStoreInterface::class));
        self::assertSame('annabel', $app->make('cache')->get('name'));
    }

    public function test_cache_config_is_publishable(): void
    {
        $app = $this->app([]);

        /** @var PublishRegistry $registry */
        $registry = $app->make(PublishRegistry::class);
        $resources = $registry->matching(CacheServiceProvider::class, 'cache');

        self::assertCount(1, $resources);
        self::assertSame($app->getBasePath() . '/config/cache.php', $resources[0]['destination']);
        self::assertFileExists($resources[0]['source']);
    }

    /**
     * @param array<string, mixed> $configuration
     */
    private function app(array $configuration): Application
    {
        Application::resetInstance();

        $app = new Application(__DIR__ . '/../..', null, false);
        (new CoreServiceProvider($app))->register();

        config($configuration);

        (new CacheServiceProvider($app))->register();

        return $app;
    }

    private function directory(): string
    {
        $path = sys_get_temp_dir() . '/annabel-framework-cache-' . bin2hex(random_bytes(6));
        $this->paths[] = $path;

        return $path;
    }
}
