<?php

namespace Codemonster\Xen\Modules\Core;

use Codemonster\Annabel\Providers\ServiceProvider;
use Codemonster\Router\Router;

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

        if (app()->has(Router::class)) {
            router()->setControllerFactory(static function (string $class) {
                return app()->make($class);
            });
        }

        $manager = app()->make(ModuleManager::class);
        $manager->bootAll();
    }
}
