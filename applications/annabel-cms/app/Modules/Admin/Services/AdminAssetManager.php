<?php

namespace Codemonster\Cms\Modules\Admin\Services;

use Codemonster\Annabel\Application;
use Codemonster\Cms\Support\Assets\ViteAssetManifest;

class AdminAssetManager
{
    private const ENTRY = 'resources/js/main.js';

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
            $this->app->getBasePath() . '/public/admin/assets',
            '/admin/assets',
            self::ENTRY,
            'resources/images/codemonster-icon.svg',
        ))->entrypoints(
            'Admin assets are not built. Run: npm run build:admin',
            'Admin assets are invalid. Rebuild them with: npm run build:admin',
        );
    }
}
