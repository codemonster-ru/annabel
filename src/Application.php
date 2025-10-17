<?php

namespace Codemonster\Annabel;

use Codemonster\Annabel\Contracts\ServiceProviderInterface;
use Codemonster\Annabel\Http\Request;
use Codemonster\Annabel\Http\Response;
use Codemonster\Annabel\Http\Kernel;
use Codemonster\Annabel\Container;
use Codemonster\View\View;

class Application
{
    protected static ?Application $instance = null;
    protected Kernel $kernel;
    protected ?View $view = null;
    protected string $basePath;
    protected array $providers = [];
    protected Container $container;
    protected bool $booted = false;

    public function __construct(?string $basePath = null, ?View $view = null, bool $autoBootstrap = true)
    {
        $this->basePath = $basePath ?? dirname(__DIR__);
        $this->container = new Container();

        self::$instance = $this;

        if ($autoBootstrap) {
            $this->bootstrap($view);
        }
    }

    // =====================================================
    // ===============  BOOTSTRAP PROCESS ==================
    // =====================================================

    public function bootstrap(?View $customView = null): void
    {
        if ($this->booted) {
            return;
        }

        $this->registerHelpers();
        $this->registerProviders();
        $this->initView($customView);
        $this->initKernel();

        $this->booted = true;
    }

    protected function registerHelpers(): void
    {
        $helpersPath = __DIR__ . '/helpers/*.php';

        foreach (glob($helpersPath) as $helper) {
            require_once $helper;
        }
    }

    protected function registerProviders(): void
    {
        $defaultProviders = [
            \Codemonster\Annabel\Providers\CoreServiceProvider::class,
            \Codemonster\Annabel\Providers\ViewServiceProvider::class,
        ];

        $customProvidersPath = "{$this->basePath}/bootstrap/providers";
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

            $provider = new $providerClass($this);

            $provider->register();

            if (is_callable([$provider, 'boot'])) {
                $provider->boot();
            }

            $this->providers[] = $provider;
        }
    }

    protected function initView(?View $customView = null): void
    {
        $this->view = $customView instanceof View
            ? $customView
            : $this->make(View::class);
    }

    protected function initKernel(): void
    {
        $this->kernel = $this->make(Kernel::class);
    }

    // =====================================================
    // ==================  PROVIDERS =======================
    // =====================================================

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

    public function getProviders(): array
    {
        return $this->providers;
    }

    // =====================================================
    // ====================  CORE  =========================
    // =====================================================

    public static function getInstance(): Application
    {
        if (!self::$instance) {
            throw new \RuntimeException("Application instance is not initialized");
        }

        return self::$instance;
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    public function getKernel(): Kernel
    {
        return $this->kernel;
    }

    public function getView(): View
    {
        if ($this->view === null) {
            throw new \RuntimeException('View has not been initialized yet.');
        }

        return $this->view;
    }

    public function getContainer(): Container
    {
        return $this->container;
    }

    // =====================================================
    // ====================  HTTP  =========================
    // =====================================================

    public function handle(Request $request): Response
    {
        if (!$this->booted) {
            $this->bootstrap();
        }

        return $this->kernel->handle($request);
    }

    public function run(): void
    {
        $request = Request::capture();
        $response = $this->handle($request);
        $response->send();
    }

    // =====================================================
    // ====================  ROUTES  =======================
    // =====================================================

    public function get(string $path, callable|array $handler): void
    {
        $this->kernel->getRouter()->get($path, $handler);
    }

    public function post(string $path, callable|array $handler): void
    {
        $this->kernel->getRouter()->post($path, $handler);
    }

    public function any(string $path, callable|array $handler): void
    {
        $this->kernel->getRouter()->any($path, $handler);
    }

    // =====================================================
    // ====================  HELPERS  ======================
    // =====================================================

    public static function serve(?string $basePath = null, ?View $view = null): void
    {
        (new self($basePath, $view))->run();
    }

    // =====================================================
    // ====================  CONTAINER =====================
    // =====================================================

    public function bind(string $abstract, \Closure|string $concrete, bool $singleton = false): void
    {
        $this->container->bind($abstract, $concrete, $singleton);
    }

    public function singleton(string $abstract, \Closure|string $concrete): void
    {
        $this->container->singleton($abstract, $concrete);
    }

    public function make(string $abstract): mixed
    {
        return $this->container->make($abstract);
    }

    public function has(string $abstract): bool
    {
        return $this->container->has($abstract);
    }
}
