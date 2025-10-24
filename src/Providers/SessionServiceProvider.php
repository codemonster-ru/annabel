<?php

namespace Codemonster\Annabel\Providers;

use Codemonster\Annabel\Contracts\ServiceProviderInterface;
use Codemonster\Session\Session;

class SessionServiceProvider extends ServiceProvider implements ServiceProviderInterface
{
    public function register(): void
    {
        $this->app()->singleton('session', fn() => Session::store());
    }

    public function boot(): void
    {
        Session::start('file');
    }
}
