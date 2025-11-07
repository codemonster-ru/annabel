<?php

namespace Codemonster\Annabel\Providers;

use Codemonster\Annabel\Application;
use Codemonster\Annabel\Contracts\ServiceProviderInterface;
use Codemonster\View\View;
use Codemonster\View\Locator\DefaultLocator;
use Codemonster\View\Engines\PhpEngine;
use Codemonster\Razor\RazorEngine;

class ViewServiceProvider implements ServiceProviderInterface
{
    protected Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function register(): void
    {
        $this->app->singleton(View::class, function (): View {
            $basePath = $this->app->getBasePath();
            $appViews = $basePath . '/resources/views';
            $frameworkViews = __DIR__ . '/../../resources/views';

            $paths = [];

            if (is_dir($appViews)) {
                $paths[] = $appViews;
            }

            if (is_dir($frameworkViews)) {
                $paths[] = $frameworkViews;
            }

            if (empty($paths)) {
                $paths[] = sys_get_temp_dir();
            }

            $locator = new DefaultLocator($paths);
            $phpEngine = new PhpEngine($locator, ['php', 'blade.php']);
            $engines = ['php' => $phpEngine];

            if (class_exists(RazorEngine::class)) {
                try {
                    $engines['razor'] = new RazorEngine($locator, ['razor.php']);
                } catch (\Throwable $e) {
                    trigger_error("Failed to initialize RazorEngine: {$e->getMessage()}", E_USER_WARNING);
                }
            }

            return new View($engines, 'php');
        });

        $this->app->singleton('view', fn($c) => $c->make(View::class));
    }

    public function boot(): void {}
}
