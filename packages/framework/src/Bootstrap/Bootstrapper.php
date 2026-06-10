<?php

namespace Codemonster\Annabel\Bootstrap;

use Codemonster\Annabel\Application;
use Codemonster\Annabel\Container\ServiceAttributeRegistrar;
use Codemonster\Annabel\Contracts\ServiceProviderInterface;
use Codemonster\Annabel\Http\Kernel;
use Codemonster\Annabel\Routing\RouteAttributeRegistrar;
use Codemonster\View\View;

class Bootstrapper
{
    protected Application $app;
    /** @var list<ServiceProviderInterface> */
    protected array $registeredProviders = [];
    protected PackageManifest $packageManifest;

    public function __construct(Application $app, ?PackageManifest $packageManifest = null)
    {
        $this->app = $app;
        $this->packageManifest = $packageManifest ?? new PackageManifest($app->getBasePath());
    }

    public function run(?View $customView = null): void
    {
        $this->loadEnv();
        $this->registerHelpers();
        $this->registerProviders();
        $this->initErrorHandler();
        $this->initView($customView);
        $this->initKernel();
        $this->registerAttributeRoutes();
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
        $helpers = glob($helpersPath);

        if ($helpers === false) {
            throw new \RuntimeException('Unable to discover framework helpers.');
        }

        foreach ($helpers as $helper) {
            require_once $helper;
        }
    }

    protected function registerProviders(): void
    {
        $providers = $this->resolveProviders();

        foreach ($providers as $providerClass) {
            $this->registerProvider($providerClass);
        }

        $this->registerDiscoveredServices();

        foreach ($this->registeredProviders as $provider) {
            $provider->boot();
        }
    }

    /**
     * @return list<string>
     */
    protected function resolveProviders(): array
    {
        $config = $this->providerConfig();
        $defaults = $config['defaults'] ?? true;

        if ($defaults === true) {
            $defaults = $this->defaultProviders();
        } elseif ($defaults === false) {
            $defaults = [];
        }

        if (!is_array($defaults)) {
            throw new \RuntimeException('Provider defaults must be an array, true, or false.');
        }

        $disabled = $this->normalizeProviderList($config['disabled'] ?? []);
        $providers = array_values(array_filter(
            $this->normalizeProviderList($defaults),
            fn (string $provider): bool => !in_array($provider, $disabled, true),
        ));

        $extra = $this->normalizeProviderList($config['extra'] ?? $config['providers'] ?? []);
        $providers = array_merge($providers, $extra);

        $packages = $config['packages'] ?? [];

        if ($packages === false) {
            $packages = ['discover' => false];
        }

        if (!is_array($packages)) {
            throw new \RuntimeException('Package provider config must be an array or false.');
        }

        if (($packages['discover'] ?? true) !== false) {
            $dontDiscover = $this->normalizeProviderList($packages['dont_discover'] ?? []);
            $cachePath = $packages['cache_path'] ?? null;

            if ($cachePath !== null && !is_string($cachePath)) {
                throw new \RuntimeException('Package manifest cache path must be a string or null.');
            }

            $providers = array_merge($providers, $this->packageManifest->providers(
                $dontDiscover,
                ($packages['cache'] ?? true) !== false,
                $cachePath,
            ));
        }

        if (($config['discover'] ?? true) !== false) {
            $path = $config['path'] ?? $this->app->getBasePath() . '/bootstrap/providers';

            if (is_string($path)) {
                $providers = array_merge($providers, $this->discoverProviders($path));
            }
        }

        return array_values(array_filter(
            array_unique($providers),
            fn (string $provider): bool => !in_array($provider, $disabled, true),
        ));
    }

    /**
     * @return list<class-string<ServiceProviderInterface>>
     */
    protected function defaultProviders(): array
    {
        return [
            \Codemonster\Annabel\Providers\CoreServiceProvider::class,
            \Codemonster\Annabel\Providers\AssetServiceProvider::class,
            \Codemonster\Annabel\Providers\CacheServiceProvider::class,
            \Codemonster\Annabel\Providers\EventServiceProvider::class,
            \Codemonster\Annabel\Providers\HttpClientServiceProvider::class,
            \Codemonster\Annabel\Providers\LoggingServiceProvider::class,
            \Codemonster\Annabel\Providers\MailServiceProvider::class,
            \Codemonster\Annabel\Providers\QueueServiceProvider::class,
            \Codemonster\Annabel\Providers\ScheduleServiceProvider::class,
            \Codemonster\Annabel\Providers\ValidationServiceProvider::class,
            \Codemonster\Annabel\Providers\DatabaseServiceProvider::class,
            \Codemonster\Annabel\Providers\ViewServiceProvider::class,
            \Codemonster\Annabel\Providers\SessionServiceProvider::class,
            \Codemonster\Annabel\Providers\AuthServiceProvider::class,
            \Codemonster\Annabel\Providers\FilesystemServiceProvider::class,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function providerConfig(): array
    {
        $config = $this->applicationConfig();
        $providers = $config['providers'] ?? [];

        if (!is_array($providers)) {
            throw new \RuntimeException('Application provider config must be an array.');
        }

        if (array_is_list($providers)) {
            return ['extra' => $providers];
        }

        foreach ($providers as $key => $_) {
            if (!is_string($key)) {
                throw new \RuntimeException('Application provider config keys must be strings.');
            }
        }

        /** @var array<string, mixed> $providers */
        return $providers;
    }

    /**
     * @return array<string, mixed>
     */
    protected function applicationConfig(): array
    {
        $cache = ConfigCache::path($this->app->getBasePath());
        $file = $this->app->getBasePath() . '/config/app.php';

        if (is_file($cache)) {
            $cached = require $cache;

            if (!is_array($cached) || !is_array($cached['app'] ?? null)) {
                throw new \RuntimeException("Application config cache [{$cache}] is invalid.");
            }

            $config = $cached['app'];
        } else {
            if (!is_file($file)) {
                return [];
            }

            $config = require $file;
        }

        if (!is_array($config)) {
            throw new \RuntimeException("Application config [$file] must return an array.");
        }

        foreach ($config as $key => $_) {
            if (!is_string($key)) {
                throw new \RuntimeException('Application config keys must be strings.');
            }
        }

        /** @var array<string, mixed> $config */
        return $config;
    }

    /**
     * @return list<string>
     */
    protected function normalizeProviderList(mixed $providers): array
    {
        if (is_string($providers)) {
            return [$providers];
        }

        if (!is_array($providers)) {
            return [];
        }

        $normalized = [];

        foreach ($providers as $provider) {
            if (is_string($provider) && $provider !== '') {
                $normalized[] = $provider;
            }
        }

        return $normalized;
    }

    /**
     * @return list<string>
     */
    protected function discoverProviders(string $path): array
    {
        if (!is_dir($path)) {
            return [];
        }

        $providers = [];
        $files = glob(rtrim($path, '/') . '/*.php');

        if ($files === false) {
            throw new \RuntimeException("Unable to discover providers in [$path].");
        }

        foreach ($files as $file) {
            require_once $file;

            $className = $this->resolveClassFromFile($file);

            if ($className && class_exists($className)) {
                $providers[] = $className;
            }
        }

        return $providers;
    }

    protected function registerProvider(string $providerClass): void
    {
        if (!is_subclass_of($providerClass, ServiceProviderInterface::class)) {
            throw new \RuntimeException(
                "Service provider [$providerClass] must implement " . ServiceProviderInterface::class,
            );
        }

        $provider = new $providerClass($this->app);
        $provider->register();

        $this->registeredProviders[] = $provider;
        $this->app->addProvider($provider);
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

    protected function registerDiscoveredServices(): void
    {
        $config = $this->applicationConfig()['services'] ?? [];

        if (!is_array($config)) {
            throw new \RuntimeException('Application service discovery config must be an array.');
        }

        /** @var array<string, mixed> $config */
        (new ServiceAttributeRegistrar($this->app->getContainer()))->register($config);
    }

    protected function registerAttributeRoutes(): void
    {
        if ($this->shouldSkipAttributeRoutes()) {
            return;
        }

        $routing = $this->applicationConfig()['routing'] ?? [];

        if (!is_array($routing)) {
            throw new \RuntimeException('Application routing config must be an array.');
        }

        $config = $routing['attributes'] ?? [];

        if (!is_array($config)) {
            throw new \RuntimeException('Application attribute routing config must be an array.');
        }

        /** @var array<string, mixed> $config */
        (new RouteAttributeRegistrar($this->app->getKernel()->getRouter()))->register($config);
    }

    protected function shouldSkipAttributeRoutes(): bool
    {
        return PHP_SAPI !== 'cli' && is_file(RouteCache::path($this->app->getBasePath()));
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
                    echo (string) $response;
                }
            });
        } catch (\Throwable $e) {
            $message = 'Fatal: ' . $e->getMessage() . PHP_EOL;

            if (defined('STDERR') && is_resource(\STDERR)) {
                fwrite(\STDERR, $message);
            } else {
                error_log($message);
            }
        }
    }
}
