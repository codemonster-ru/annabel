<?php

namespace Codemonster\Xen\Modules\Core\Controllers;

class SystemController
{
    public function info(): string
    {
        view()->addNamespace('core', __DIR__ . '/../Views');

        return view('core::system-info', [
            'site'     => config('xen.site_name', 'Xen CMS'),
            'base'     => base_path(),
            'locale'   => config('xen.locale', 'en'),
            'timezone' => config('xen.timezone', 'UTC'),
        ]);
    }
}
