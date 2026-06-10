<?php

namespace Codemonster\Annabel\Tests\Providers;

use Codemonster\Annabel\Application;
use Codemonster\Annabel\Providers\CoreServiceProvider;
use Codemonster\Annabel\Providers\FilesystemServiceProvider;
use Codemonster\Annabel\Publishing\PublishRegistry;
use Codemonster\Config\Config;
use Codemonster\Filesystem\Contracts\FilesystemInterface;
use Codemonster\Filesystem\FilesystemManager;
use PHPUnit\Framework\TestCase;

class FilesystemServiceProviderTest extends TestCase
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

    public function test_filesystem_services_are_registered(): void
    {
        $root = $this->directory();
        $app = $this->app([
            'filesystem.default' => 'local',
            'filesystem.disks.local.driver' => 'local',
            'filesystem.disks.local.root' => $root,
        ]);

        $disk = $app->make(FilesystemInterface::class);
        $disk->put('hello.txt', 'Hello');

        self::assertInstanceOf(FilesystemManager::class, $app->make(FilesystemManager::class));
        self::assertSame('Hello', file_get_contents($root . '/hello.txt'));
    }

    public function test_filesystem_config_is_publishable(): void
    {
        $app = $this->app([]);

        /** @var PublishRegistry $registry */
        $registry = $app->make(PublishRegistry::class);
        $resources = $registry->matching(FilesystemServiceProvider::class, 'filesystem');

        self::assertCount(1, $resources);
        self::assertSame($app->getBasePath() . '/config/filesystem.php', $resources[0]['destination']);
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

        (new FilesystemServiceProvider($app))->register();

        return $app;
    }

    private function directory(): string
    {
        $path = sys_get_temp_dir() . '/annabel-framework-filesystem-' . bin2hex(random_bytes(6));
        mkdir($path, 0770, true);
        $this->paths[] = $path . '/hello.txt';
        $this->paths[] = $path;

        return $path;
    }
}
