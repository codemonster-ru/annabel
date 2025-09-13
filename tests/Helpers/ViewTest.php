<?php

use PHPUnit\Framework\TestCase;
use Annabel\Application;
use Annabel\Http\Response;

class ViewTest extends TestCase
{
    protected Application $app;

    protected function setUp(): void
    {
        $basePath = __DIR__ . '/fixtures';

        mkdir("$basePath/resources/views", 0777, true);

        file_put_contents("$basePath/resources/views/hello.php", 'Hello, <?= $name ?>!');

        $this->app = new Application($basePath);
    }

    public function testViewHelperReturnsResponse(): void
    {
        $response = view('hello', ['name' => 'World']);

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testViewHelperRendersTemplate(): void
    {
        $response = view('hello', ['name' => 'Annabel']);

        $this->assertSame('Hello, Annabel!', $response->getContent());
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
