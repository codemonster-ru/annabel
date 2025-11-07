<?php

namespace Codemonster\Annabel\Bootstrap;

use Codemonster\Annabel\Application;
use Codemonster\Annabel\Contracts\ServiceProviderInterface;
use Codemonster\View\View;
use Codemonster\Annabel\Http\Kernel;

class Bootstrapper
{
    protected Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function run(?View $customView = null): void
    {
        $this->registerHelpers();
        $this->registerProviders();
        $this->initView($customView);
        $this->initKernel();
    }

    protected function registerHelpers(): void
    {
        $helpersPath = __DIR__ . '/../helpers/*.php';

        foreach (glob($helpersPath) as $helper) {
            require_once $helper;
        }
    }

    protected function registerProviders(): void
    {
        $defaultProviders = [
            \Codemonster\Annabel\Providers\CoreServiceProvider::class,
            \Codemonster\Annabel\Providers\ViewServiceProvider::class,
            \Codemonster\Annabel\Providers\SessionServiceProvider::class,
        ];

        $basePath = $this->app->getBasePath();
        $customProvidersPath = "{$basePath}/bootstrap/providers";
        $userProviders = [];

        if (is_dir($customProvidersPath)) {
            foreach (glob($customProvidersPath . '/*.php') as $file) {
                require_once $file;

                $className = $this->resolveClassFromFile($file);

                if ($className && class_exists($className)) {
                    $userProviders[] = $className;
                }
            }
        }

        $providers = array_merge($defaultProviders, $userProviders);

        foreach ($providers as $providerClass) {
            if (!is_subclass_of($providerClass, ServiceProviderInterface::class)) {
                throw new \RuntimeException(
                    "Service provider [$providerClass] must implement " . ServiceProviderInterface::class
                );
            }

            $provider = new $providerClass($this->app);
            $provider->register();

            if (is_callable([$provider, 'boot'])) {
                $provider->boot();
            }

            $this->app->addProvider($provider);
        }
    }

    protected function resolveClassFromFile(string $file): ?string
    {
        $contents = file_get_contents($file);

        if (!preg_match('/namespace\s+([^;]+);/i', $contents, $nsMatch)) {
            return null;
        }

        if (!preg_match('/class\s+([a-zA-Z0-9_]+)/i', $contents, $classMatch)) {
            return null;
        }

        return trim($nsMatch[1]) . '\\' . trim($classMatch[1]);
    }

    protected function initView(?View $customView = null): void
    {
        $this->app->setView($customView instanceof View ? $customView : $this->app->make(View::class));
    }

    protected function initKernel(): void
    {
        $this->app->setKernel($this->app->make(Kernel::class));
    }
}
