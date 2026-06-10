<?php

namespace Codemonster\Annabel\Providers;

use Codemonster\Annabel\Container;
use Codemonster\Validation\Validator;

class ValidationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app()->singleton(Validator::class, fn (): Validator => new Validator());
        $this->app()->singleton('validator', fn (Container $app): Validator => $app->make(Validator::class));
    }
}
