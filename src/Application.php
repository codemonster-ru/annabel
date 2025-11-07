<?php

namespace Codemonster\Annabel;

use Codemonster\Annabel\Bootstrap\Bootstrapper;
use Codemonster\Annabel\Contracts\ServiceProviderInterface;
use Codemonster\Http\Request;
use Codemonster\Http\Response;
use Codemonster\Annabel\Http\Kernel;
use Codemonster\View\View;

class Application
{
    protected static ?Application $instance = null;

    protected string $basePath;
    protected Container $container;
    protected ?Kernel $kernel = null;
    protected ?View $view = null;
    protected array $providers = [];
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

        (new Bootstrapper($this))->run($customView);

        $this->booted = true;
    }

    public function addProvider(ServiceProviderInterface $provider): void
    {
        $this->providers[] = $provider;
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
        if (!$this->kernel) {
            throw new \RuntimeException('Kernel is not initialized.');
        }

        return $this->kernel;
    }

    public function setKernel(Kernel $kernel): void
    {
        $this->kernel = $kernel;
    }

    public function getView(): View
    {
        if (!$this->view) {
            throw new \RuntimeException('View is not initialized.');
        }

        return $this->view;
    }

    public function setView(View $view): void
    {
        $this->view = $view;
    }

    public function getContainer(): Container
    {
        return $this->container;
    }

    public function getProviders(): array
    {
        return $this->providers;
    }

    // =====================================================
    // ====================  HTTP  =========================
    // =====================================================

    public function handle(Request $request): Response
    {
        if (!$this->booted) {
            $this->bootstrap();
        }

        return $this->getKernel()->handle($request);
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
        $this->getKernel()->getRouter()->get($path, $handler);
    }

    public function post(string $path, callable|array $handler): void
    {
        $this->getKernel()->getRouter()->post($path, $handler);
    }

    public function any(string $path, callable|array $handler): void
    {
        $this->getKernel()->getRouter()->any($path, $handler);
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
