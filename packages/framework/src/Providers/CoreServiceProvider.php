<?php

namespace Codemonster\Annabel\Providers;

use Codemonster\Annabel\Application;
use Codemonster\Annabel\Bootstrap\ConfigCache;
use Codemonster\Annabel\Container;
use Codemonster\Annabel\Contracts\ServiceProviderInterface;
use Codemonster\Annabel\Http\Kernel;
use Codemonster\Config\Config;
use Codemonster\Env\Env;
use Codemonster\Errors\Contracts\ExceptionHandlerInterface;
use Codemonster\Errors\Handlers\SmartExceptionHandler;
use Codemonster\Http\Request;
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

        $this->app->singleton(Env::class, function () {
            return new Env();
        });

        $this->app->singleton(Config::class, function () use ($basePath) {
            $cache = ConfigCache::path($basePath);

            if (is_file($cache)) {
                Config::loadCached($cache);
            } else {
                Config::load("{$basePath}/config");
            }

            return new Config();
        });

        $this->app->singleton('config', fn (Container $c) => $c->make(Config::class));

        $this->app->singleton(Router::class, fn () => new Router());

        $this->app->singleton('router', fn (Container $c) => $c->make(Router::class));

        $this->app->singleton(Kernel::class, fn (Container $c) => new Kernel(
            $this->app,
            $c->make(Router::class),
        ));

        $this->app->bind(Request::class, fn () => Request::capture());

        $this->app->bind('request', fn (Container $c) => $c->make(Request::class));

        $this->app->singleton(ExceptionHandlerInterface::class, function ($app) {
            $debug = env('APP_DEBUG', false, true);

            /** @param array<string, mixed> $data */
            $renderer = function (string $template, array $data) {
                $view = $this->app->getView();
                $viewData = [];

                foreach ($data as $key => $value) {
                    if (is_string($key)) {
                        $viewData[$key] = $value;
                    }
                }

                try {
                    $html = $view->render(str_replace('.', '/', $template), $viewData);
                } catch (\RuntimeException $e) {
                    if (strpos($e->getMessage(), 'View not found:') === 0) {
                        return null;
                    }

                    throw $e;
                }

                return $html;
            };

            return new SmartExceptionHandler($renderer, is_bool($debug) ? $debug : false);
        });
    }

    public function boot(): void
    {
    }
}
