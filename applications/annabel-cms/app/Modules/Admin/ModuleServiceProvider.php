<?php

namespace Codemonster\Cms\Modules\Admin;

use Codemonster\Annabel\Providers\ServiceProvider;
use Codemonster\Cms\Modules\Admin\Contracts\AdminScreenRendererInterface;
use Codemonster\Cms\Modules\Admin\Services\AdminNavigationRegistry;
use Codemonster\Cms\Modules\Admin\Services\AdminShellRenderer;
use Codemonster\Cms\Modules\Core\ModuleManager;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app()->singleton(
            AdminNavigationRegistry::class,
            fn (): AdminNavigationRegistry => new AdminNavigationRegistry(
                $this->app()->make(ModuleManager::class),
            ),
        );
        $this->app()->bind(
            AdminScreenRendererInterface::class,
            fn (): AdminScreenRendererInterface => $this->app()->make(AdminShellRenderer::class),
        );
    }
}
