<?php

namespace Codemonster\Annabel;

use Codemonster\Annabel\Bootstrap\Bootstrapper;
use Codemonster\Annabel\Bootstrap\RouteCache;
use Codemonster\Annabel\Console\CommandRegistry;
use Codemonster\Annabel\Contracts\ServiceProviderInterface;
use Codemonster\Annabel\Http\Kernel;
use Codemonster\Annabel\Publishing\PublishRegistry;
use Codemonster\Annabel\Publishing\ResourcePublisher;
use Codemonster\Http\Request;
use Codemonster\Http\Response;
use Codemonster\View\View;

class Application
{
    protected static ?Application $instance = null;

    protected string $basePath;
    protected Container $container;
    protected ?Kernel $kernel = null;
    protected ?View $view = null;
    /** @var list<ServiceProviderInterface> */
    protected array $providers = [];
    protected bool $booted = false;

    public function __construct(?string $basePath = null, ?View $view = null, bool $autoBootstrap = true)
    {
        if (self::$instance !== null) {
            throw new \RuntimeException(
                'Application instance is already initialized. Call Application::resetInstance() to re-initialize.',
            );
        }

        $this->basePath = $basePath ?? dirname(__DIR__);
        $this->container = new Container();
        $this->container->instance(self::class, $this);
        $this->container->instance(CommandRegistry::class, new CommandRegistry());
        $this->container->instance(PublishRegistry::class, new PublishRegistry());
        $this->container->instance(ResourcePublisher::class, new ResourcePublisher($this->basePath));

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
            throw new \RuntimeException('Application instance is not initialized');
        }

        return self::$instance;
    }

    public static function setInstance(Application $app): void
    {
        self::$instance = $app;
    }

    public static function resetInstance(): void
    {
        self::$instance = null;
    }

    public static function isInitialized(): bool
    {
        return self::$instance !== null;
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

    /**
     * @return list<ServiceProviderInterface>
     */
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

    public function loadCachedRoutes(string $path): void
    {
        if (!$this->booted) {
            $this->bootstrap();
        }

        RouteCache::load($path, $this->getKernel()->getRouter());
    }

    /**
     * @param callable|array{mixed, mixed} $handler
     */
    public function get(string $path, callable|array $handler): void
    {
        if (!$this->booted) {
            $this->bootstrap();
        }

        $this->getKernel()->getRouter()->get($path, $handler);
    }

    /**
     * @param callable|array{mixed, mixed} $handler
     */
    public function post(string $path, callable|array $handler): void
    {
        if (!$this->booted) {
            $this->bootstrap();
        }

        $this->getKernel()->getRouter()->post($path, $handler);
    }

    /**
     * @param callable|array{mixed, mixed} $handler
     */
    public function any(string $path, callable|array $handler): void
    {
        if (!$this->booted) {
            $this->bootstrap();
        }

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

    /** @param \Closure(Container, array<string, mixed>=): mixed|class-string $concrete */
    public function bind(string $abstract, \Closure|string $concrete, bool $singleton = false): void
    {
        $this->container->bind($abstract, $concrete, $singleton);
    }

    /** @param \Closure(Container, array<string, mixed>=): mixed|class-string $concrete */
    public function singleton(string $abstract, \Closure|string $concrete): void
    {
        $this->container->singleton($abstract, $concrete);
    }

    /**
     * @template T of object
     * @param class-string<T>|string $abstract
     * @param array<string, mixed> $parameters
     * @return ($abstract is class-string<T> ? T : mixed)
     */
    public function make(string $abstract, array $parameters = []): mixed
    {
        return $this->container->make($abstract, $parameters);
    }

    public function has(string $abstract): bool
    {
        return $this->container->has($abstract);
    }
}
