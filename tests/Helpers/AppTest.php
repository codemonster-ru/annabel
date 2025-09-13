<?php

use PHPUnit\Framework\TestCase;
use Annabel\Application;

class AppTest extends TestCase
{
    protected Application $app;

    protected function setUp(): void
    {
        $basePath = __DIR__ . '/fixtures';

        mkdir("$basePath/resources/views", 0777, true);

        $this->app = new Application($basePath);
    }

    public function testAppHelperReturnsApplicationInstance(): void
    {
        $this->assertInstanceOf(Application::class, app());
    }

    public function testAppHelperIsSingleton(): void
    {
        $app1 = app();
        $app2 = app();

        $this->assertSame($app1, $app2, 'app() should always return the same Application instance');
    }

    protected function tearDown(): void
    {
        $basePath = __DIR__ . '/fixtures';

        if (is_dir($basePath)) {
            $this->deleteDir($basePath);
        }
    }

    private function deleteDir(string $dir): void
    {
        if (!is_dir($dir))
            return;
        foreach (scandir($dir) as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $path = "$dir/$file";

            is_dir($path) ? $this->deleteDir($path) : unlink($path);
        }

        rmdir($dir);
    }
}
