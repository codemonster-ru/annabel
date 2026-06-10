<?php

namespace Codemonster\Annabel\Tests\Container;

use Codemonster\Annabel\Application;
use Codemonster\Config\Config;
use PHPUnit\Framework\TestCase;

class ServiceAttributeRegistrarTest extends TestCase
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

    public function test_service_attributes_and_interface_autoconfiguration_bind_services(): void
    {
        $basePath = $this->appPath();
        $servicePath = $this->directory($basePath . '/app/Services');

        file_put_contents($servicePath . '/GreetingService.php', <<<'PHP'
<?php

namespace App\Services;

use Codemonster\Annabel\Container\Attributes\Service;

interface Greeter
{
    public function greet(): string;
}

#[Service(singleton: true)]
class GreetingService implements Greeter
{
    public function greet(): string
    {
        return 'hello';
    }
}
PHP);

        $this->writeAppConfig($basePath, [
            'providers' => [
                'defaults' => true,
                'discover' => false,
            ],
            'services' => [
                'enabled' => true,
                'paths' => [$servicePath],
                'autoconfigure' => [
                    'App\\Services\\Greeter' => ['singleton' => true],
                ],
            ],
        ]);

        $app = new Application($basePath);

        $this->assertSame('hello', $app->make('App\\Services\\Greeter')->greet());
        $this->assertSame(
            $app->make('App\\Services\\GreetingService'),
            $app->make('App\\Services\\GreetingService'),
        );
    }

    private function appPath(): string
    {
        return $this->directory(sys_get_temp_dir() . '/annabel-services-' . bin2hex(random_bytes(6)));
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

        while ($current !== dirname($current) && str_contains($current, 'annabel-services-')) {
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
