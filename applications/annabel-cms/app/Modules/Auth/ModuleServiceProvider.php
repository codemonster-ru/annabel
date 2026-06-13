<?php

namespace Codemonster\Cms\Modules\Auth;

use Codemonster\Annabel\Providers\ServiceProvider;
use Codemonster\Cms\Modules\Auth\Contracts\AuthenticatorInterface;
use Codemonster\Cms\Modules\Auth\Contracts\UserSessionInterface;
use Codemonster\Cms\Modules\Auth\Services\AuthenticationService;
use Codemonster\Cms\Modules\Auth\Services\SessionUserService;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app()->bind(AuthenticatorInterface::class, AuthenticationService::class);
        $this->app()->bind(UserSessionInterface::class, SessionUserService::class);
    }
}
