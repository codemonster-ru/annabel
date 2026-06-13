<?php

namespace Codemonster\Xen\Modules\Core;

use Codemonster\Annabel\Providers\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    protected static bool $booted = false;

    public function register(): void
    {
        app()->singleton(ModuleManager::class, fn() => new ModuleManager(app()));
    }

    public function boot(): void
    {
        if (self::$booted) {
            return;
        }

        self::$booted = true;

        $manager = app()->make(ModuleManager::class);
        $manager->bootAll();
    }
}
