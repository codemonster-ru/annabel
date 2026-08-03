<?php

namespace Codemonster\Cms\Tests\Unit\Support\Assets;

use Codemonster\Cms\Support\Assets\ViteAssetManifest;
use PHPUnit\Framework\TestCase;

class ViteAssetManifestTest extends TestCase
{
    public function testItIncludesStylesFromStaticImportsBeforeEntryStyles(): void
    {
        $publicPath = sys_get_temp_dir() . '/annabel-vite-manifest-' . bin2hex(random_bytes(8));
        mkdir($publicPath . '/.vite', 0777, true);

        $manifest = [
            'resources/js/main.js' => [
                'file' => 'main.js',
                'imports' => ['_feature-a.js', '_feature-b.js'],
                'css' => ['main.css'],
            ],
            '_feature-a.js' => [
                'file' => 'feature-a.js',
                'imports' => ['_foundation.js'],
                'css' => ['feature-a.css', 'shared.css'],
            ],
            '_feature-b.js' => [
                'file' => 'feature-b.js',
                'imports' => ['_foundation.js'],
                'css' => ['feature-b.css', 'shared.css'],
            ],
            '_foundation.js' => [
                'file' => 'foundation.js',
                'imports' => ['resources/js/main.js'],
                'css' => ['foundation.css'],
            ],
        ];

        file_put_contents($publicPath . '/main.js', 'export {};');
        file_put_contents($publicPath . '/.vite/manifest.json', json_encode($manifest, JSON_THROW_ON_ERROR));

        try {
            $assets = (new ViteAssetManifest(
                $publicPath,
                '/assets',
                'resources/js/main.js',
            ))->entrypoints('Missing manifest.', 'Invalid manifest.');

            self::assertSame([
                '/assets/foundation.css',
                '/assets/feature-a.css',
                '/assets/shared.css',
                '/assets/feature-b.css',
                '/assets/main.css',
            ], $assets['styles']);
        } finally {
            unlink($publicPath . '/.vite/manifest.json');
            unlink($publicPath . '/main.js');
            rmdir($publicPath . '/.vite');
            rmdir($publicPath);
        }
    }
}
