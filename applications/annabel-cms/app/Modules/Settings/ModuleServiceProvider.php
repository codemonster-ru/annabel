<?php

namespace Codemonster\Cms\Modules\Settings;

use Codemonster\Annabel\Providers\ServiceProvider;
use Codemonster\Cms\Modules\Settings\Services\SiteSettings;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app()->singleton(SiteSettings::class, SiteSettings::class);
    }
}
