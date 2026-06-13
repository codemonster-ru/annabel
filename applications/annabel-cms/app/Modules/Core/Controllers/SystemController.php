<?php

namespace Codemonster\Cms\Modules\Core\Controllers;

use Codemonster\Annabel\Application;
use Codemonster\Http\Response;
use Codemonster\View\View;

class SystemController
{
    public function __construct(
        private Application $app,
        private View $view,
    ) {
    }

    public function info(): Response
    {
        return new Response($this->view->render('core::system-info', [
            'site' => config('cms.site_name', 'Annabel CMS'),
            'base' => $this->app->getBasePath(),
            'locale' => config('cms.locale', 'en'),
            'timezone' => config('cms.timezone', 'UTC'),
        ]));
    }
}
