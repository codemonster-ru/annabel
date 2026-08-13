<?php

namespace Codemonster\Cms\Providers;

use Codemonster\Annabel\Providers\ServiceProvider;
use Codemonster\Razor\Components\ComponentRegistry;
use Codemonster\Ui\UiComponentProvider;

final class CodeMonsterUiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app()->make(ComponentRegistry::class)->register(new UiComponentProvider());
    }
}
