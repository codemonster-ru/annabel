<?php

namespace Codemonster\Annabel\Providers;

use Codemonster\Annabel\Application;
use Codemonster\Annabel\Container;
use Codemonster\Annabel\Contracts\ServiceProviderInterface;
use Codemonster\Annabel\Http\Kernel;
use Codemonster\Http\Request;
use Codemonster\Config\Config;
use Codemonster\Env\Env;
use Codemonster\Router\Router;
use Codemonster\Errors\Contracts\ExceptionHandlerInterface;
use Codemonster\Errors\Handlers\SmartExceptionHandler;
use Codemonster\Annabel\Cache\ArrayCache;
use Codemonster\Annabel\Cache\FileCache;
use Codemonster\Annabel\Events\EventDispatcher;
use Codemonster\Annabel\Events\ListenerProvider;
use Codemonster\Annabel\Logging\FileLogger;
use Codemonster\Annabel\Validation\Validator;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\SimpleCache\CacheInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

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
            Config::load("{$basePath}/config");

            return new Config();
        });

        $this->app->singleton('config', fn(Container $c) => $c->make(Config::class));

        $this->app->singleton(Router::class, fn() => new Router());

        $this->app->singleton('router', fn(Container $c) => $c->make(Router::class));

        $this->app->singleton(Kernel::class, fn(Container $c) => new Kernel(
            $this->app,
            $c->make(Router::class)
        ));

        $this->app->singleton(LoggerInterface::class, function () use ($basePath) {
            $driver = config('logging.default', 'null');

            if ($driver === 'file') {
                $path = config('logging.channels.file.path', "{$basePath}/storage/logs/annabel.log");

                return new FileLogger(is_string($path) ? $path : "{$basePath}/storage/logs/annabel.log");
            }

            return new NullLogger();
        });

        $this->app->singleton('logger', fn(Container $c) => $c->make(LoggerInterface::class));

        $this->app->singleton(CacheInterface::class, function () use ($basePath) {
            $driver = config('cache.default', 'array');

            if ($driver === 'file') {
                $path = config('cache.stores.file.path', "{$basePath}/storage/cache");

                return new FileCache(is_string($path) ? $path : "{$basePath}/storage/cache");
            }

            return new ArrayCache();
        });

        $this->app->singleton('cache', fn(Container $c) => $c->make(CacheInterface::class));

        $this->app->singleton(ListenerProvider::class, fn() => new ListenerProvider());
        $this->app->singleton(ListenerProviderInterface::class, fn(Container $c) => $c->make(ListenerProvider::class));
        $this->app->singleton(EventDispatcher::class, fn(Container $c) => new EventDispatcher($c->make(ListenerProvider::class)));
        $this->app->singleton(EventDispatcherInterface::class, fn(Container $c) => $c->make(EventDispatcher::class));
        $this->app->singleton('events', fn(Container $c) => $c->make(EventDispatcherInterface::class));

        $this->app->singleton(Validator::class, fn() => new Validator());
        $this->app->singleton('validator', fn(Container $c) => $c->make(Validator::class));

        $this->app->bind(Request::class, fn() => Request::capture());

        $this->app->bind('request', fn(Container $c) => $c->make(Request::class));

        $this->app->singleton(ExceptionHandlerInterface::class, function ($app) {
            $debug = env('APP_DEBUG', false, true);

            $renderer = function (string $template, array $data) {
                $view = $this->app->getView();

                try {
                    $html = $view->render(str_replace('.', '/', $template), $data);
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

    public function boot(): void {}
}
