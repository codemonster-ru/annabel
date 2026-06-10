<?php

namespace Codemonster\Annabel\Tests\Routing;

use Codemonster\Annabel\Application;
use Codemonster\Config\Config;
use Codemonster\Http\Request;
use PHPUnit\Framework\TestCase;

class RouteAttributeRegistrarTest extends TestCase
{
    private array $paths = [];

    protected function tearDown(): void
    {
        Config::reset();
        Application::resetInstance();

        foreach (array_reverse($this->paths) as $path) {
            $this->removePath($path);
        }
    }

    public function test_attribute_routes_are_registered_from_configured_paths(): void
    {
        $basePath = $this->appPath();
        $controllerPath = $this->directory($basePath . '/app/Controllers');

        file_put_contents($controllerPath . '/UserController.php', <<<'PHP'
<?php

namespace App\Controllers;

use Codemonster\Annabel\Routing\Attributes\Get;
use Codemonster\Annabel\Routing\Attributes\Post;
use Codemonster\Annabel\Routing\Attributes\RoutePrefix;

#[RoutePrefix('/users')]
class UserController
{
    #[Get('/{id}', name: 'users.show', where: ['id' => '\d+'])]
    public function show(string $id): string
    {
        return "user:{$id}";
    }

    #[Post('/', middleware: 'auth')]
    public function store(): string
    {
        return 'stored';
    }
}
PHP);

        $this->writeAppConfig($basePath, [
            'providers' => [
                'defaults' => true,
                'discover' => false,
            ],
            'routing' => [
                'attributes' => [
                    'enabled' => true,
                    'paths' => [$controllerPath],
                ],
            ],
        ]);

        $app = new Application($basePath);
        $router = $app->getKernel()->getRouter();

        $this->assertSame('/users/42', $router->route('users.show', ['id' => 42]));
        $this->assertSame('user:42', $app->handle(new Request('GET', '/users/42'))->getContent());

        $post = $router->dispatch('POST', '/users');
        $this->assertNotNull($post);
        $this->assertCount(1, $post->getMiddleware());
    }

    private function appPath(): string
    {
        return $this->directory(sys_get_temp_dir() . '/annabel-attribute-routes-' . bin2hex(random_bytes(6)));
    }

    private function writeAppConfig(string $basePath, array $config): void
    {
        $configPath = $this->directory($basePath . '/config');
        file_put_contents($configPath . '/app.php', "<?php\nreturn " . var_export($config, true) . ";\n");
        $this->paths[] = $configPath . '/app.php';
    }

    private function directory(string $path): string
    {
        if (!is_dir($path)) {
            mkdir($path, 0770, true);
        }

        $segments = [];
        $current = $path;

        while ($current !== dirname($current) && str_contains($current, 'annabel-attribute-routes-')) {
            $segments[] = $current;
            $current = dirname($current);
        }

        foreach ($segments as $segment) {
            if (!in_array($segment, $this->paths, true)) {
                $this->paths[] = $segment;
            }
        }

        return $path;
    }

    private function removePath(string $path): void
    {
        if (is_file($path)) {
            @unlink($path);

            return;
        }

        if (!is_dir($path)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $file) {
            $file->isDir() ? @rmdir($file->getPathname()) : @unlink($file->getPathname());
        }

        @rmdir($path);
    }
}
