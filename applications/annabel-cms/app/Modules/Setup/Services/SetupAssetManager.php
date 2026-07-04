<?php

namespace Codemonster\Cms\Modules\Setup\Services;

use Codemonster\Annabel\Application;
use Codemonster\Cms\Support\Assets\ViteAssetManifest;

class SetupAssetManager
{
    private const ENTRY = 'resources/js/main.js';
    private const FAVICON = 'resources/images/setup-brand.svg';

    public function __construct(
        private Application $app,
    ) {
    }

    /**
     * @return array{script: string, styles: array<int, string>, favicon: string|null}
     */
    public function entrypoints(): array
    {
        return (new ViteAssetManifest(
            $this->app->getBasePath() . '/public/setup/assets',
            '/setup/assets',
            self::ENTRY,
            self::FAVICON,
        ))->entrypoints(
            'Setup assets are not built. Run: npm run build:setup',
            'Setup assets are invalid. Rebuild them with: npm run build:setup',
        );
    }
}
