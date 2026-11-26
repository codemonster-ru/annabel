<?php

namespace Codemonster\Cms\Tests\Unit\Support\Assets;

use Codemonster\Cms\Support\Assets\ViteAssetManifest;
use PHPUnit\Framework\Attributes\DataProvider;
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

    #[DataProvider('entryBundles')]
    public function testItValidatesEntryBundle(?string $bundle, bool $isValid): void
    {
        $publicPath = sys_get_temp_dir() . '/annabel-vite-manifest-' . bin2hex(random_bytes(8));
        mkdir($publicPath . '/.vite', 0777, true);
        file_put_contents($publicPath . '/.vite/manifest.json', '{"resources/js/main.js":{"file":"main.js"}}');
        if ($bundle !== null) {
            file_put_contents($publicPath . '/main.js', $bundle);
        }

        try {
            if (!$isValid) {
                $this->expectException(\RuntimeException::class);
                $this->expectExceptionMessage('Invalid bundle.');
            }

            $assets = (new ViteAssetManifest($publicPath, '/assets', 'resources/js/main.js'))
                ->entrypoints('Missing manifest.', 'Invalid bundle.');

            self::assertSame('/assets/main.js', $assets['script']);
        } finally {
            if ($bundle !== null) {
                unlink($publicPath . '/main.js');
            }
            unlink($publicPath . '/.vite/manifest.json');
            rmdir($publicPath . '/.vite');
            rmdir($publicPath);
        }
    }

    /** @return iterable<string, array{string|null, bool}> */
    public static function entryBundles(): iterable
    {
        yield 'inert strings and comments' => [<<<'JS'
            const key = Symbol.for(`@codemonster-ru/ui:overlay-scroll`);
            const example = "import '@codemonster-ru/example'";
            /* export * from '@codemonster-ru/comment'; */
            export { key, example };
            JS, true];
        yield 'static import' => ['import{CmIcon}from"@codemonster-ru/ui-vue";', false];
        yield 'side-effect import' => ['import "@codemonster-ru/ui-css/styles.css";', false];
        yield 're-export' => ['export * from "@codemonster-ru/ui-vue";', false];
        yield 'dynamic import' => ['const ui=import(/* detail */ `@codemonster-ru/ui-vue`);', false];
        yield 'missing entry' => [null, false];
    }
}
