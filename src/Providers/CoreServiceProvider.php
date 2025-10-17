<?php

namespace Codemonster\Annabel\Providers;

use Codemonster\Annabel\Application;
use Codemonster\Annabel\Contracts\ServiceProviderInterface;
use Codemonster\Annabel\Http\Kernel;
use Codemonster\Annabel\Http\Request;
use Codemonster\Config\Config;
use Codemonster\Env\Env;
use Codemonster\Router\Router;

class CoreServiceProvider implements ServiceProviderInterface
{
    protected Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function register(): void
    {
        $basePath = $this->app->getBasePath();

        $this->app->singleton(Env::class, function () use ($basePath) {
            $envPath = "{$basePath}/.env";

            if (file_exists($envPath)) {
                Env::load($envPath);
            }

            return new Env();
        });

        $this->app->singleton(Config::class, function () use ($basePath) {
            Config::load("{$basePath}/config");

            return new Config();
        });

        $this->app->singleton(Router::class, fn() => new Router());

        $this->app->singleton(Kernel::class, fn($c) => new Kernel(
            $this->app,
            $c->make(Router::class)
        ));

        $this->app->bind(Request::class, fn() => Request::capture());
    }

    public function boot(): void {}
}
