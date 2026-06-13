<?php

namespace Codemonster\Cms\Modules\Admin\Services;

use Codemonster\Annabel\Application;

class AdminAssetManager
{
    private const ENTRY = 'resources/js/main.js';

    public function __construct(
        private Application $app,
    ) {
    }

    /**
     * @return array{script: string, styles: array<int, string>}
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

        if (!is_array($entry) || !is_string($entry['file'] ?? null)) {
            throw new \RuntimeException('Admin Vite manifest is invalid.');
        }

        $styles = [];

        foreach ((array) ($entry['css'] ?? []) as $css) {
            if (is_string($css)) {
                $styles[] = '/admin/assets/' . ltrim($css, '/');
            }
        }

        return [
            'script' => '/admin/assets/' . ltrim($entry['file'], '/'),
            'styles' => $styles,
        ];
    }
}
