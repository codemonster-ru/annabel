<?php

namespace Codemonster\Annabel\Providers;

use Codemonster\Annabel\Application;
use Codemonster\Annabel\Container;
use Codemonster\Annabel\Contracts\ServiceProviderInterface;
use Codemonster\Razor\Components\ComponentRegistry;
use Codemonster\Razor\RazorEngine;
use Codemonster\View\Engines\PhpEngine;
use Codemonster\View\Locator\DefaultLocator;
use Codemonster\View\View;

class ViewServiceProvider implements ServiceProviderInterface
{
    protected Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function register(): void
    {
        $this->app->singleton(ComponentRegistry::class, ComponentRegistry::class);

        $this->app->singleton(View::class, function (Container $container): View {
            $basePath = $this->app->getBasePath();
            $appViews = $basePath . '/resources/views';
            $frameworkViews = realpath(__DIR__ . '/../../resources/views');

            $paths = [];

            if (is_dir($appViews)) {
                $paths[] = $appViews;
            }

            if ($frameworkViews && is_dir($frameworkViews)) {
                $paths[] = $frameworkViews;
            }

            if (empty($paths)) {
                $paths[] = sys_get_temp_dir();
            }

            $locator = new DefaultLocator($paths);
            $phpEngine = new PhpEngine($locator, ['php', 'blade.php']);
            $engines = ['php' => $phpEngine];

            $engines['razor'] = new RazorEngine(
                $locator,
                ['razor.php'],
                $basePath . '/storage/cache/views',
                $container->make(ComponentRegistry::class),
            );

            return new View($engines, 'php');
        });

        $this->app->singleton('view', fn (Container $c) => $c->make(View::class));
    }

    public function boot(): void
    {
    }
}
