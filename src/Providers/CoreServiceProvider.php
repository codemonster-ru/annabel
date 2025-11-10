<?php

namespace Codemonster\Annabel\Providers;

use Codemonster\Annabel\Application;
use Codemonster\Annabel\Contracts\ServiceProviderInterface;
use Codemonster\Annabel\Http\Kernel;
use Codemonster\Http\Request;
use Codemonster\Config\Config;
use Codemonster\Env\Env;
use Codemonster\Router\Router;
use Codemonster\Errors\Contracts\ExceptionHandlerInterface;
use Codemonster\Errors\Handlers\SmartExceptionHandler;

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

        $this->app->singleton('config', fn($c) => $c->make(Config::class));

        $this->app->singleton(Router::class, fn() => new Router());

        $this->app->singleton('router', fn($c) => $c->make(Router::class));

        $this->app->singleton(Kernel::class, fn($c) => new Kernel(
            $this->app,
            $c->make(Router::class)
        ));

        $this->app->bind(Request::class, fn() => Request::capture());

        $this->app->bind('request', fn($c) => $c->make(Request::class));

        $this->app->singleton(ExceptionHandlerInterface::class, function ($app) {
            $debug = env('APP_DEBUG', false);

            $renderer = function (string $template, array $data) {
                $view = $this->app->getView();
                $html = $view->render(str_replace('.', '/', $template), $data);

                return $html;
            };

            return new SmartExceptionHandler($renderer, $debug);
        });
    }

    public function boot(): void {}
}
