<?php

namespace Codemonster\Xen\Modules\Pages;

use Codemonster\Annabel\Providers\ServiceProvider;
use Codemonster\View\View;

class ModuleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $view = app(View::class);
        $view->addNamespace('pages', __DIR__ . '/Views');

        $routes = __DIR__ . '/routes/web.php';

        if (file_exists($routes)) {
            require $routes;
        }
    }
}
