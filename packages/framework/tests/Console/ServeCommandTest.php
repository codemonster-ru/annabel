<?php

namespace Codemonster\Annabel\Tests\Console;

use Codemonster\Annabel\Console\Commands\ServeCommand;
use PHPUnit\Framework\TestCase;

class ServeCommandTest extends TestCase
{
    /** @var list<string> */
    private array $paths = [];
    private ?string $previousRequestUri = null;

    protected function setUp(): void
    {
        $previousRequestUri = $_SERVER['REQUEST_URI'] ?? null;
        $this->previousRequestUri = is_string($previousRequestUri) ? $previousRequestUri : null;
    }

    protected function tearDown(): void
    {
        if ($this->previousRequestUri === null) {
            unset($_SERVER['REQUEST_URI']);
        } else {
            $_SERVER['REQUEST_URI'] = $this->previousRequestUri;
        }

        foreach (array_reverse($this->paths) as $path) {
            if (is_file($path)) {
                @unlink($path);
            } elseif (is_dir($path)) {
                @rmdir($path);
            }
        }
    }

    public function test_router_serves_existing_public_files(): void
    {
        $public = $this->directory();
        $assets = $this->directory($public . '/admin/assets/assets');
        file_put_contents($assets . '/admin.js', 'console.log("admin");');
        $this->paths[] = $assets . '/admin.js';

        $index = $public . '/index.php';
        file_put_contents($index, '<?php return "front";');
        $this->paths[] = $index;

        $router = (new TestServeCommand())->makeRouter($public, $index);
        $this->paths[] = $router;

        $_SERVER['REQUEST_URI'] = '/admin/assets/assets/admin.js';

        self::assertFalse(require $router);
    }

    public function test_router_falls_through_to_front_controller_for_application_routes(): void
    {
        $public = $this->directory();
        $index = $public . '/index.php';
        file_put_contents($index, '<?php return "front";');
        $this->paths[] = $index;

        $router = (new TestServeCommand())->makeRouter($public, $index);
        $this->paths[] = $router;

        $_SERVER['REQUEST_URI'] = '/admin';

        self::assertSame('front', require $router);
    }

    private function directory(?string $path = null): string
    {
        $path ??= sys_get_temp_dir() . '/annabel-serve-' . bin2hex(random_bytes(6));

        if (!is_dir($path)) {
            mkdir($path, 0770, true);
        }

        $this->paths[] = $path;

        return $path;
    }
}

class TestServeCommand extends ServeCommand
{
    public function makeRouter(string $publicDir, string $index): string
    {
        return $this->createRouterScript($publicDir, $index);
    }
}
