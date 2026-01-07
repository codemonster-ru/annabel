<?php

namespace Codemonster\Xen\Modules\Pages;

use Codemonster\Annabel\Providers\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        view()->addNamespace('pages', __DIR__ . '/views');
    }
}
