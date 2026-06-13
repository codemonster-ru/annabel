<?php

namespace Codemonster\Cms\Modules\Core;

use Codemonster\Annabel\Providers\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app()->singleton(
            ModuleManager::class,
            fn () => new ModuleManager($this->app()),
        );
    }

    public function boot(): void
    {
        $manager = $this->app()->make(ModuleManager::class);
        $manager->bootAll();
    }
}
