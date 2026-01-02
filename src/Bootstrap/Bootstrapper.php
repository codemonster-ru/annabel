<?php

namespace Codemonster\Annabel\Bootstrap;

use Codemonster\Annabel\Application;
use Codemonster\Annabel\Contracts\ServiceProviderInterface;
use Codemonster\View\View;
use Codemonster\Annabel\Http\Kernel;
use Throwable;

class Bootstrapper
{
    protected Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function run(?View $customView = null): void
    {
        $this->loadEnv();
        $this->registerHelpers();
        $this->registerProviders();
        $this->initErrorHandler();
        $this->initView($customView);
        $this->initKernel();
    }

    protected function loadEnv(): void
    {
        $basePath = $this->app->getBasePath();
        $envFile = $basePath . '/.env';

        if (is_file($envFile)) {
            \Codemonster\Env\Env::load($envFile);
        }
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
            \Codemonster\Annabel\Providers\DatabaseServiceProvider::class,
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

        if ($contents === false) {
            return null;
        }

        $tokens = token_get_all($contents);
        $namespace = '';
        $classes = [];

        for ($i = 0, $count = count($tokens); $i < $count; $i++) {
            $token = $tokens[$i];

            if (!is_array($token)) {
                continue;
            }

            if ($token[0] === T_NAMESPACE) {
                $namespace = '';

                for ($j = $i + 1; $j < $count; $j++) {
                    $part = $tokens[$j];

                    if (is_array($part) && ($part[0] === T_STRING || $part[0] === T_NAME_QUALIFIED)) {
                        $namespace .= $part[1];
                    } elseif (is_array($part) && $part[0] === T_NS_SEPARATOR) {
                        $namespace .= '\\';
                    } elseif ($part === ';' || $part === '{') {
                        break;
                    }
                }
            }

            if ($token[0] === T_CLASS) {
                $prev = $tokens[$i - 1] ?? null;

                if (is_array($prev) && $prev[0] === T_NEW) {
                    continue;
                }

                for ($j = $i + 1; $j < $count; $j++) {
                    $part = $tokens[$j];

                    if (is_array($part) && $part[0] === T_STRING) {
                        $classes[] = $part[1];
                        break;
                    }
                }
            }
        }

        if ($classes === []) {
            return null;
        }

        if (count($classes) > 1) {
            throw new \RuntimeException("Multiple classes found in provider file [$file].");
        }

        $class = $classes[0];

        return $namespace !== '' ? $namespace . '\\' . $class : $class;
    }

    protected function initView(?View $customView = null): void
    {
        $this->app->setView($customView instanceof View ? $customView : $this->app->make(View::class));
    }

    protected function initKernel(): void
    {
        $this->app->setKernel($this->app->make(Kernel::class));
    }

    protected function initErrorHandler(): void
    {
        if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
            return;
        }

        try {
            $handler = $this->app->make(\Codemonster\Errors\Contracts\ExceptionHandlerInterface::class);

            set_exception_handler(function (\Throwable $e) use ($handler) {
                $response = $handler->handle($e);

                if ($response instanceof \Codemonster\Http\Response) {
                    $response->send();
                } else {
                    echo (string)$response;
                }
            });
        } catch (\Throwable $e) {
            $message = "Fatal: " . $e->getMessage() . PHP_EOL;

            if (defined('STDERR') && is_resource(\STDERR)) {
                fwrite(\STDERR, $message);
            } else {
                error_log($message);
            }
        }
    }
}
