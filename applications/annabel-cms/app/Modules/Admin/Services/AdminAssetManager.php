<?php

namespace Codemonster\Cms\Modules\Admin\Services;

use Codemonster\Annabel\Application;

class AdminAssetManager
{
    private const ENTRY = 'resources/js/main.js';
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
        $publicPath = $this->app->getBasePath() . '/public/admin/assets';
        $manifestPath = $publicPath . '/.vite/manifest.json';

        if (!is_file($manifestPath)) {
            throw new \RuntimeException(
                'Admin assets are not built. Run: npm run build:admin',
            );
        }

        $contents = file_get_contents($manifestPath);
        $manifest = is_string($contents) ? json_decode($contents, true) : null;
        $entry = is_array($manifest) ? ($manifest[self::ENTRY] ?? null) : null;

        if (
            !is_array($entry)
            || !is_string($entry['file'] ?? null)
        ) {
            throw new \RuntimeException('Admin Vite manifest is invalid.');
        }

        $scriptFile = $publicPath . '/' . ltrim($entry['file'], '/');

        if (!$this->isValidBundle($scriptFile)) {
            throw new \RuntimeException(
                'Admin assets are invalid. Rebuild them with: npm run build:admin',
            );
        }

        $styles = [];

        foreach ((array) ($entry['css'] ?? []) as $css) {
            if (is_string($css)) {
                $styles[] = '/admin/assets/' . ltrim($css, '/');
            }
        }

        $favicon = null;
        $faviconFile = is_array($manifest)
            ? ($manifest['resources/images/codemonster-icon.svg']['file'] ?? null)
            : null;

        if (is_string($faviconFile) && $faviconFile !== '') {
            $favicon = '/admin/assets/' . ltrim($faviconFile, '/');
        }

        return [
            'script' => '/admin/assets/' . ltrim($entry['file'], '/'),
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
