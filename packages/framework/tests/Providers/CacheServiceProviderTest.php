<?php

namespace Codemonster\Annabel\Tests\Providers;

use Codemonster\Annabel\Application;
use Codemonster\Annabel\Providers\CacheServiceProvider;
use Codemonster\Annabel\Providers\CoreServiceProvider;
use Codemonster\Annabel\Publishing\PublishRegistry;
use Codemonster\Cache\CacheManager;
use Codemonster\Cache\Contracts\CacheStoreInterface;
use Codemonster\Config\Config;
use Codemonster\DateTime\FrozenClock;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use Psr\SimpleCache\CacheInterface;

class CacheServiceProviderTest extends TestCase
{
    /** @var list<string> */
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
        $defaultCache = $app->make('cache');

        self::assertInstanceOf(CacheManager::class, $app->make(CacheManager::class));
        self::assertInstanceOf(CacheStoreInterface::class, $app->make(CacheStoreInterface::class));
        self::assertInstanceOf(CacheInterface::class, $defaultCache);
        self::assertSame('annabel', $defaultCache->get('name'));
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

    public function test_cache_manager_uses_the_registered_clock(): void
    {
        $app = $this->app([
            'cache.default' => 'array',
            'cache.stores.array.driver' => 'array',
        ]);
        $clock = new FrozenClock(new \DateTimeImmutable('2026-06-09 10:15:00 UTC'));
        $app->getContainer()->instance(ClockInterface::class, $clock);
        $manager = $app->make(CacheManager::class);

        $property = new \ReflectionProperty(CacheManager::class, 'clock');

        self::assertSame($clock, $property->getValue($manager));
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
