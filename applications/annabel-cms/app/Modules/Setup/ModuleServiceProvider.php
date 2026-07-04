<?php

namespace Codemonster\Cms\Modules\Setup;

use Codemonster\Annabel\Providers\ServiceProvider;
use Codemonster\Cms\Modules\Setup\Services\EnvironmentFile;
use Codemonster\Cms\Modules\Setup\Services\SetupAssetManager;
use Codemonster\Cms\Modules\Setup\Services\SystemRequirements;
use Codemonster\Cms\Support\Installation\InstallationState;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app()->singleton(
            InstallationState::class,
            fn () => new InstallationState(base_path('storage/app/setup/installed.json')),
        );

        $this->app()->singleton(
            EnvironmentFile::class,
            fn () => new EnvironmentFile(base_path('.env')),
        );

        $this->app()->singleton(
            SetupAssetManager::class,
            fn () => new SetupAssetManager($this->app()),
        );

        $this->app()->singleton(
            SystemRequirements::class,
            fn () => new SystemRequirements(base_path()),
        );
    }
}
