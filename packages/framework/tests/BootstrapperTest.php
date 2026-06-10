<?php

namespace Codemonster\Annabel\Tests;

use Codemonster\Annabel\Application;
use Codemonster\Annabel\Bootstrap\Bootstrapper;
use Codemonster\Annabel\Bootstrap\CacheFile;
use Codemonster\Annabel\Bootstrap\ConfigCache;
use Codemonster\Annabel\Bootstrap\PackageManifest;
use Codemonster\Annabel\Contracts\ServiceProviderInterface;
use PHPUnit\Framework\TestCase;

class BootstrapperTest extends TestCase
{
    protected function tearDown(): void
    {
        Application::resetInstance();
        TestLifecycleProvider::$events = [];
    }

    public function test_resolve_class_from_file_throws_on_multiple_classes()
    {
        Application::resetInstance();

        $app = new Application(__DIR__ . '/..', null, false);
        $bootstrapper = new TestBootstrapper($app);

        $file = tempnam(sys_get_temp_dir(), 'annabel-provider-');
        $php = "<?php\nnamespace TestProvider;\nclass First {}\nclass Second {}\n";

        file_put_contents($file, $php);

        $this->expectException(\RuntimeException::class);

        try {
            $bootstrapper->exposeResolve($file);
        } finally {
            @unlink($file);

            Application::resetInstance();
        }
    }

    public function test_provider_config_can_disable_defaults_and_add_extra_providers()
    {
        $basePath = $this->makeAppConfig([
            'providers' => [
                'defaults' => [
                    TestLifecycleProvider::class,
                    TestSecondLifecycleProvider::class,
                ],
                'disabled' => [
                    TestSecondLifecycleProvider::class,
                ],
                'extra' => [
                    TestThirdLifecycleProvider::class,
                ],
                'discover' => false,
            ],
        ]);

        $app = new Application($basePath, null, false);
        $bootstrapper = new TestBootstrapper($app);

        $this->assertSame([
            TestLifecycleProvider::class,
            TestThirdLifecycleProvider::class,
        ], $bootstrapper->exposeResolveProviders());
    }

    public function test_providers_are_all_registered_before_any_provider_is_booted()
    {
        $basePath = $this->makeAppConfig([
            'providers' => [
                'defaults' => false,
                'extra' => [
                    TestLifecycleProvider::class,
                    TestSecondLifecycleProvider::class,
                ],
                'discover' => false,
            ],
        ]);

        $app = new Application($basePath, null, false);
        $bootstrapper = new TestBootstrapper($app);

        $bootstrapper->exposeRegisterProviders();

        $this->assertSame([
            'first.register',
            'second.register',
            'first.boot',
            'second.boot',
        ], TestLifecycleProvider::$events);
    }

    public function test_package_providers_are_merged_with_application_providers()
    {
        $basePath = $this->makeAppConfig([
            'providers' => [
                'defaults' => false,
                'extra' => [
                    TestLifecycleProvider::class,
                ],
                'discover' => false,
                'packages' => [
                    'discover' => true,
                    'cache' => false,
                ],
            ],
        ]);

        $app = new Application($basePath, null, false);
        $manifest = new TestPackageManifest($basePath, [
            TestSecondLifecycleProvider::class,
        ]);
        $bootstrapper = new TestBootstrapper($app, $manifest);

        $this->assertSame([
            TestLifecycleProvider::class,
            TestSecondLifecycleProvider::class,
        ], $bootstrapper->exposeResolveProviders());
    }

    public function test_disabled_providers_are_removed_after_package_discovery()
    {
        $basePath = $this->makeAppConfig([
            'providers' => [
                'defaults' => false,
                'disabled' => [
                    TestSecondLifecycleProvider::class,
                ],
                'discover' => false,
                'packages' => [
                    'discover' => true,
                    'cache' => false,
                ],
            ],
        ]);

        $app = new Application($basePath, null, false);
        $manifest = new TestPackageManifest($basePath, [
            TestSecondLifecycleProvider::class,
        ]);
        $bootstrapper = new TestBootstrapper($app, $manifest);

        $this->assertSame([], $bootstrapper->exposeResolveProviders());
    }

    public function test_provider_config_is_loaded_from_application_cache(): void
    {
        $basePath = sys_get_temp_dir() . '/annabel-bootstrapper-cache-' . bin2hex(random_bytes(6));
        mkdir($basePath . '/bootstrap/cache', 0770, true);
        CacheFile::write(ConfigCache::path($basePath), [
            'app' => [
                'providers' => [
                    'defaults' => false,
                    'extra' => [TestLifecycleProvider::class],
                    'discover' => false,
                ],
            ],
        ]);

        $app = new Application($basePath, null, false);
        $bootstrapper = new TestBootstrapper($app);

        try {
            $this->assertSame([TestLifecycleProvider::class], $bootstrapper->exposeResolveProviders());
        } finally {
            @unlink(ConfigCache::path($basePath));
            @rmdir($basePath . '/bootstrap/cache');
            @rmdir($basePath . '/bootstrap');
            @rmdir($basePath);
        }
    }

    private function makeAppConfig(array $config): string
    {
        $basePath = sys_get_temp_dir() . '/annabel-bootstrapper-' . bin2hex(random_bytes(6));
        $configPath = $basePath . '/config';

        mkdir($configPath, 0770, true);
        file_put_contents(
            $configPath . '/app.php',
            "<?php\nreturn " . var_export($config, true) . ";\n",
        );

        return $basePath;
    }
}

class TestBootstrapper extends Bootstrapper
{
    public function exposeResolve(string $file): ?string
    {
        return $this->resolveClassFromFile($file);
    }

    public function exposeResolveProviders(): array
    {
        return $this->resolveProviders();
    }

    public function exposeRegisterProviders(): void
    {
        $this->registerProviders();
    }
}

class TestLifecycleProvider implements ServiceProviderInterface
{
    public static array $events = [];

    public function __construct(protected Application $app)
    {
    }

    public function register(): void
    {
        self::$events[] = 'first.register';
    }

    public function boot(): void
    {
        self::$events[] = 'first.boot';
    }
}

class TestSecondLifecycleProvider implements ServiceProviderInterface
{
    public function __construct(protected Application $app)
    {
    }

    public function register(): void
    {
        TestLifecycleProvider::$events[] = 'second.register';
    }

    public function boot(): void
    {
        TestLifecycleProvider::$events[] = 'second.boot';
    }
}

class TestThirdLifecycleProvider implements ServiceProviderInterface
{
    public function __construct(protected Application $app)
    {
    }

    public function register(): void
    {
    }

    public function boot(): void
    {
    }
}

class TestPackageManifest extends PackageManifest
{
    public function __construct(string $basePath, private array $discovered)
    {
        parent::__construct($basePath, fn () => []);
    }

    public function providers(
        array $dontDiscover = [],
        bool $useCache = true,
        ?string $cachePath = null,
    ): array {
        return $this->discovered;
    }
}
