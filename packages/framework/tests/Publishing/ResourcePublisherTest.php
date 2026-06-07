<?php

use Codemonster\Annabel\Application;
use Codemonster\Annabel\Providers\ServiceProvider;
use Codemonster\Annabel\Publishing\PublishRegistry;
use Codemonster\Annabel\Publishing\ResourcePublisher;
use PHPUnit\Framework\TestCase;

class ResourcePublisherTest extends TestCase
{
    private array $paths = [];

    protected function setUp(): void
    {
        Application::resetInstance();
    }

    protected function tearDown(): void
    {
        Application::resetInstance();

        foreach (array_reverse($this->paths) as $path) {
            $this->remove($path);
        }
    }

    public function test_provider_can_register_tagged_publishable_resources()
    {
        $basePath = $this->directory('annabel-publish-app-');
        $app = new Application($basePath, null, false);
        $provider = new TestPublishingProvider($app);
        $provider->register();

        /** @var PublishRegistry $registry */
        $registry = $app->make(PublishRegistry::class);
        $resources = $registry->matching(TestPublishingProvider::class, 'config');

        $this->assertCount(1, $resources);
        $this->assertSame('/package/config.php', $resources[0]['source']);
        $this->assertSame($basePath . '/config/package.php', $resources[0]['destination']);
    }

    public function test_registry_merges_tags_for_duplicate_resources()
    {
        $registry = new PublishRegistry();
        $paths = ['/source' => '/destination'];

        $registry->add(TestPublishingProvider::class, $paths, 'config');
        $registry->add(TestPublishingProvider::class, $paths, 'example');

        $this->assertCount(1, $registry->all());
        $this->assertSame(['config', 'example'], $registry->all()[0]['tags']);
    }

    public function test_it_publishes_files_without_overwriting_by_default()
    {
        $basePath = $this->directory('annabel-publish-app-');
        $source = $this->file('annabel-source-', 'first');
        $destination = $basePath . '/config/example.php';
        $publisher = new ResourcePublisher($basePath);
        $resource = $this->resource($source, $destination);

        $first = $publisher->publish([$resource]);
        file_put_contents($source, 'second');
        $second = $publisher->publish([$resource]);
        $forced = $publisher->publish([$resource], true);

        $this->assertSame([$destination], $first->published);
        $this->assertSame([$destination], $second->skipped);
        $this->assertSame([$destination], $forced->published);
        $this->assertSame('second', file_get_contents($destination));
    }

    public function test_it_publishes_directory_contents()
    {
        $basePath = $this->directory('annabel-publish-app-');
        $source = $this->directory('annabel-publish-source-');
        mkdir($source . '/nested', 0770, true);
        file_put_contents($source . '/nested/view.php', 'view');
        $destination = $basePath . '/resources/views/vendor/example';

        (new ResourcePublisher($basePath))->publish([
            $this->resource($source, $destination),
        ]);

        $this->assertSame('view', file_get_contents($destination . '/nested/view.php'));
    }

    public function test_destination_outside_application_is_rejected()
    {
        $basePath = $this->directory('annabel-publish-app-');
        $source = $this->file('annabel-source-', 'content');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('must be inside application base path');

        (new ResourcePublisher($basePath))->publish([
            $this->resource($source, dirname($basePath) . '/outside.php'),
        ]);
    }

    public function test_destination_symlink_escape_is_rejected()
    {
        if (!function_exists('symlink')) {
            $this->markTestSkipped('Symbolic links are not supported.');
        }

        $basePath = $this->directory('annabel-publish-app-');
        $outside = $this->directory('annabel-publish-outside-');
        $source = $this->file('annabel-source-', 'content');
        $link = $basePath . '/linked';

        if (!@symlink($outside, $link)) {
            $this->markTestSkipped('Unable to create a symbolic link.');
        }

        $this->paths[] = $link;
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('contains symbolic link');

        (new ResourcePublisher($basePath))->publish([
            $this->resource($source, $link . '/escaped.php'),
        ]);
    }

    /**
     * @return array{provider: class-string, source: string, destination: string, tags: list<string>}
     */
    private function resource(string $source, string $destination): array
    {
        return [
            'provider' => TestPublishingProvider::class,
            'source' => $source,
            'destination' => $destination,
            'tags' => ['test'],
        ];
    }

    private function directory(string $prefix): string
    {
        $path = sys_get_temp_dir() . '/' . $prefix . bin2hex(random_bytes(6));
        mkdir($path, 0770, true);
        $this->paths[] = $path;

        return $path;
    }

    private function file(string $prefix, string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), $prefix);
        file_put_contents($path, $contents);
        $this->paths[] = $path;

        return $path;
    }

    private function remove(string $path): void
    {
        if (is_link($path) || is_file($path)) {
            @unlink($path);

            return;
        }

        if (!is_dir($path)) {
            return;
        }

        foreach (scandir($path) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $this->remove($path . DIRECTORY_SEPARATOR . $item);
        }

        @rmdir($path);
    }
}

class TestPublishingProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->publishes([
            '/package/config.php' => $this->app()->getBasePath() . '/config/package.php',
        ], ['config', 'example']);
    }
}
