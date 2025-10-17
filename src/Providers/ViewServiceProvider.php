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
            $viewDir = $basePath . '/resources/views';

            if (!is_dir($viewDir)) {
                $viewDir = sys_get_temp_dir();
            }

            $locator = new DefaultLocator([$viewDir]);

            $phpEngine = new PhpEngine($locator, ['php', 'blade.php']);
            $engines = ['php' => $phpEngine];

            if (class_exists(RazorEngine::class)) {
                try {
                    $engines['razor'] = new RazorEngine($locator, ['razor.php']);
                } catch (\Throwable $e) {
                    trigger_error("Failed to initialize RazorEngine: {$e->getMessage()}", E_USER_WARNING);
                }
            }

            $view = new View($engines, 'php');

            return $view;
        });
    }

    public function boot(): void {}
}
