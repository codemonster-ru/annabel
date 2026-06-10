<?php

namespace Codemonster\Annabel\Tests\Providers;

use Codemonster\Annabel\Application;
use Codemonster\Annabel\Assets\Vite;
use Codemonster\Annabel\Providers\AssetServiceProvider;
use Codemonster\Annabel\Providers\CoreServiceProvider;
use Codemonster\Annabel\Publishing\PublishRegistry;
use Codemonster\Config\Config;
use PHPUnit\Framework\TestCase;

class AssetServiceProviderTest extends TestCase
{
    protected function tearDown(): void
    {
        Application::resetInstance();
        Config::reset();
    }

    public function test_asset_services_are_registered(): void
    {
        $app = $this->app([]);

        self::assertInstanceOf(Vite::class, $app->make(Vite::class));
        self::assertInstanceOf(Vite::class, $app->make('vite'));
    }

    public function test_asset_config_is_publishable(): void
    {
        $app = $this->app([]);

        /** @var PublishRegistry $registry */
        $registry = $app->make(PublishRegistry::class);
        $resources = $registry->matching(AssetServiceProvider::class, 'assets');

        self::assertCount(1, $resources);
        self::assertSame($app->getBasePath() . '/config/assets.php', $resources[0]['destination']);
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

        (new AssetServiceProvider($app))->register();

        return $app;
    }
}
