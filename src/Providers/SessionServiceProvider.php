<?php

namespace Codemonster\Annabel\Providers;

use Codemonster\Annabel\Contracts\ServiceProviderInterface;
use Codemonster\Session\Session;

class SessionServiceProvider extends ServiceProvider implements ServiceProviderInterface
{
    public function register(): void
    {
        $store = Session::store();

        $this->app()->singleton('session', fn() => $store);
    }

    public function boot(): void
    {
        $store = $this->app()->make('session');
        $store->start();
    }
}
