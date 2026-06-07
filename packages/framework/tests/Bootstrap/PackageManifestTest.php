<?php

use Codemonster\Annabel\Bootstrap\PackageManifest;
use PHPUnit\Framework\TestCase;

class PackageManifestTest extends TestCase
{
    private array $paths = [];

    protected function tearDown(): void
    {
        foreach (array_reverse($this->paths) as $path) {
            if (is_file($path)) {
                @unlink($path);
            } elseif (is_dir($path)) {
                $this->removeDirectory($path);
            }
        }
    }

    public function test_it_discovers_providers_from_package_metadata()
    {
        $basePath = $this->directory('annabel-manifest-app-');
        $packagePath = $this->package('vendor/example', [
            'Example\\Package\\ServiceProvider',
        ]);
        $manifest = new PackageManifest($basePath, fn() => [
            'vendor/example' => $packagePath,
        ]);

        $this->assertSame([
            'Example\\Package\\ServiceProvider',
        ], $manifest->providers(useCache: false));
    }

    public function test_it_supports_package_opt_out()
    {
        $basePath = $this->directory('annabel-manifest-app-');
        $packagePath = $this->package('vendor/example', [
            'Example\\Package\\ServiceProvider',
        ]);
        $manifest = new PackageManifest($basePath, fn() => [
            'vendor/example' => $packagePath,
        ]);

        $this->assertSame([], $manifest->providers(['vendor/example'], false));
        $this->assertSame([], $manifest->providers(['*'], false));
    }

    public function test_cache_is_invalidated_when_package_metadata_changes()
    {
        $basePath = $this->directory('annabel-manifest-app-');
        $packagePath = $this->package('vendor/example', [
            'Example\\Package\\FirstProvider',
        ]);
        $manifest = new PackageManifest($basePath, fn() => [
            'vendor/example' => $packagePath,
        ]);

        $this->assertSame([
            'Example\\Package\\FirstProvider',
        ], $manifest->providers());

        $this->writeComposer($packagePath, 'vendor/example', [
            'Example\\Package\\SecondProvider',
        ]);

        $this->assertSame([
            'Example\\Package\\SecondProvider',
        ], $manifest->providers());
        $this->assertFileExists($basePath . '/bootstrap/cache/packages.php');
    }

    public function test_invalid_provider_metadata_is_rejected()
    {
        $basePath = $this->directory('annabel-manifest-app-');
        $packagePath = $this->directory('annabel-package-');
        file_put_contents($packagePath . '/composer.json', json_encode([
            'name' => 'vendor/example',
            'extra' => [
                'annabel' => [
                    'providers' => 42,
                ],
            ],
        ], JSON_THROW_ON_ERROR));
        $this->paths[] = $packagePath . '/composer.json';
        $manifest = new PackageManifest($basePath, fn() => [
            'vendor/example' => $packagePath,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('extra.annabel.providers');

        $manifest->providers(useCache: false);
    }

    /**
     * @param list<string> $providers
     */
    private function package(string $name, array $providers): string
    {
        $path = $this->directory('annabel-package-');
        $this->writeComposer($path, $name, $providers);

        return $path;
    }

    /**
     * @param list<string> $providers
     */
    private function writeComposer(string $path, string $name, array $providers): void
    {
        $file = $path . '/composer.json';

        file_put_contents($file, json_encode([
            'name' => $name,
            'extra' => [
                'annabel' => [
                    'providers' => $providers,
                ],
            ],
        ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

        if (!in_array($file, $this->paths, true)) {
            $this->paths[] = $file;
        }
    }

    private function directory(string $prefix): string
    {
        $path = sys_get_temp_dir() . '/' . $prefix . bin2hex(random_bytes(6));
        mkdir($path, 0770, true);
        $this->paths[] = $path;

        return $path;
    }

    private function removeDirectory(string $path): void
    {
        $items = scandir($path);

        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $target = $path . DIRECTORY_SEPARATOR . $item;

            if (is_dir($target)) {
                $this->removeDirectory($target);
            } else {
                @unlink($target);
            }
        }

        @rmdir($path);
    }
}
