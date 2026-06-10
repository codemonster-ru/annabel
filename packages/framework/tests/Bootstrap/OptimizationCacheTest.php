<?php

namespace Codemonster\Annabel\Tests\Bootstrap;

use Codemonster\Annabel\Bootstrap\ConfigCache;
use Codemonster\Annabel\Bootstrap\RouteCache;
use Codemonster\Config\Config;
use Codemonster\Router\Router;
use PHPUnit\Framework\TestCase;

class OptimizationCacheTest extends TestCase
{
    /** @var list<string> */
    private array $paths = [];

    protected function tearDown(): void
    {
        Config::reset();

        foreach (array_reverse($this->paths) as $path) {
            if (is_file($path)) {
                @unlink($path);
            } elseif (is_dir($path)) {
                @rmdir($path);
            }
        }
    }

    public function test_configuration_can_be_cached_loaded_and_cleared(): void
    {
        $basePath = $this->applicationDirectory();
        file_put_contents($basePath . '/config/app.php', "<?php\nreturn ['name' => 'Annabel'];\n");
        file_put_contents($basePath . '/config/database.php', "<?php\nreturn ['driver' => 'sqlite'];\n");

        self::assertSame(2, ConfigCache::write($basePath));
        self::assertFileExists(ConfigCache::path($basePath));

        Config::loadCached(ConfigCache::path($basePath));

        self::assertSame('Annabel', Config::get('app.name'));
        self::assertSame('sqlite', Config::get('database.driver'));
        self::assertTrue(ConfigCache::clear($basePath));
        self::assertFileDoesNotExist(ConfigCache::path($basePath));
    }

    public function test_routes_can_be_cached_and_restored(): void
    {
        $basePath = $this->applicationDirectory();
        $router = new Router();
        $router->add(['GET', 'HEAD'], '/users/{id}', [TestRouteController::class, 'show'])
            ->middleware('auth', ['role', 'admin'])
            ->where('id', '\d+')
            ->name('users.show');

        self::assertSame(1, RouteCache::write($basePath, $router));

        $restored = new Router();
        RouteCache::load(RouteCache::path($basePath), $restored);
        $route = $restored->dispatch('HEAD', '/users/42');

        self::assertNotNull($route);
        self::assertSame([TestRouteController::class, 'show'], $route->handler);
        self::assertSame('users.show', $route->getName());
        self::assertSame([['auth', ['role', 'admin']]], $route->getMiddleware());
        self::assertSame(['id' => '\d+'], $route->getConstraints());
        self::assertTrue(RouteCache::clear($basePath));
    }

    public function test_closure_routes_cannot_be_cached(): void
    {
        $basePath = $this->applicationDirectory();
        $router = new Router();
        $router->get('/closure', fn (): string => 'no');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('cannot be cached');

        RouteCache::write($basePath, $router);
    }

    public function test_invalid_route_cache_metadata_is_rejected(): void
    {
        $basePath = $this->applicationDirectory();
        file_put_contents(
            RouteCache::path($basePath),
            "<?php\nreturn [['methods' => ['GET', 1], 'path' => '/', 'handler' => 'handler']];\n",
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('invalid route methods');

        RouteCache::load(RouteCache::path($basePath), new Router());
    }

    private function applicationDirectory(): string
    {
        $basePath = sys_get_temp_dir() . '/annabel-optimization-' . bin2hex(random_bytes(6));
        $configPath = $basePath . '/config';
        $cachePath = $basePath . '/bootstrap/cache';
        mkdir($configPath, 0770, true);
        mkdir($cachePath, 0770, true);

        $this->paths[] = $configPath . '/app.php';
        $this->paths[] = $configPath . '/database.php';
        $this->paths[] = ConfigCache::path($basePath);
        $this->paths[] = RouteCache::path($basePath);
        $this->paths[] = $cachePath;
        $this->paths[] = $basePath . '/bootstrap';
        $this->paths[] = $configPath;
        $this->paths[] = $basePath;

        return $basePath;
    }
}

class TestRouteController
{
    public function show(): void
    {
    }
}
