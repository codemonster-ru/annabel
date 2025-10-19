<?php

namespace Codemonster\Xen\Modules\Admin;

use Codemonster\Annabel\Providers\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        view()->addNamespace('admin', __DIR__ . '/Views');
    }
}
