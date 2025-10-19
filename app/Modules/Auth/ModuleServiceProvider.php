<?php

namespace Codemonster\Xen\Modules\Auth;

use Codemonster\Annabel\Providers\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        view()->addNamespace('auth', __DIR__ . '/Views');
    }
}
