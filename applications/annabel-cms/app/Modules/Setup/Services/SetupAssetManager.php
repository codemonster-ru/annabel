<?php

namespace Codemonster\Cms\Modules\Setup\Services;

use Codemonster\Annabel\Application;

class SetupAssetManager
{
    private const ENTRY = 'resources/js/main.js';
    private const FAVICON = 'resources/images/setup-brand.svg';
    private const UNRESOLVED_IMPORT_NEEDLE = '@codemonster-ru/';

    public function __construct(
        private Application $app,
    ) {
    }

    /**
     * @return array{script: string, styles: array<int, string>, favicon: string|null}
     */
    public function entrypoints(): array
    {
        $publicPath = $this->app->getBasePath() . '/public/setup/assets';
        $manifestPath = $publicPath . '/.vite/manifest.json';

        if (!is_file($manifestPath)) {
            throw new \RuntimeException(
                'Setup assets are not built. Run: npm run build:setup',
            );
        }

        $contents = file_get_contents($manifestPath);
        $manifest = is_string($contents) ? json_decode($contents, true) : null;
        $entry = is_array($manifest) ? ($manifest[self::ENTRY] ?? null) : null;

        if (
            !is_array($entry)
            || !is_string($entry['file'] ?? null)
        ) {
            throw new \RuntimeException('Setup Vite manifest is invalid.');
        }

        $scriptFile = $publicPath . '/' . ltrim($entry['file'], '/');

        if (!$this->isValidBundle($scriptFile)) {
            throw new \RuntimeException(
                'Setup assets are invalid. Rebuild them with: npm run build:setup',
            );
        }

        $styles = [];

        foreach ((array) ($entry['css'] ?? []) as $css) {
            if (is_string($css)) {
                $styles[] = '/setup/assets/' . ltrim($css, '/');
            }
        }

        $favicon = null;
        $faviconEntry = is_array($manifest) ? ($manifest[self::FAVICON] ?? null) : null;

        if (is_array($faviconEntry) && is_string($faviconEntry['file'] ?? null)) {
            $favicon = '/setup/assets/' . ltrim($faviconEntry['file'], '/');
        }

        return [
            'script' => '/setup/assets/' . ltrim($entry['file'], '/'),
            'styles' => $styles,
            'favicon' => $favicon,
        ];
    }

    private function isValidBundle(string $bundlePath): bool
    {
        if (!is_file($bundlePath)) {
            return false;
        }

        $contents = file_get_contents($bundlePath);

        if (!is_string($contents)) {
            return false;
        }

        return !str_contains($contents, self::UNRESOLVED_IMPORT_NEEDLE);
    }
}
