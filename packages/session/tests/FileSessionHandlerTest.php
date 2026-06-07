<?php

use Codemonster\Session\Handlers\FileSessionHandler;
use Codemonster\Session\Store;
use PHPUnit\Framework\TestCase;

final class FileSessionHandlerTest extends TestCase
{
    protected string $path = '';

    protected function setUp(): void
    {
        $this->path = sys_get_temp_dir() . '/session_test_' . uniqid();

        mkdir($this->path, 0777, true);
    }

    protected function tearDown(): void
    {
        $files = glob("{$this->path}/sess_*");
        foreach ($files === false ? [] : $files as $file) {
            @unlink($file);
        }

        @rmdir($this->path);
    }

    public function testWriteAndReadSessionFile(): void
    {
        $id = bin2hex(random_bytes(16));

        $handler = new FileSessionHandler($this->path);
        $store = new Store($handler, $id);
        $store->start();
        $store->put('key', 'value');

        $handler2 = new FileSessionHandler($this->path);
        $store2 = new Store($handler2, $id);
        $store2->start();

        $this->assertSame('value', $store2->get('key'));
    }

    public function testDestroyRemovesFile(): void
    {
        $handler = new FileSessionHandler($this->path);
        $store = new Store($handler);
        $store->start();

        $store->put('foo', 'bar');

        $files = glob("{$this->path}/sess_*");

        $this->assertNotEmpty($files);

        $store->destroy();

        $filesAfter = glob("{$this->path}/sess_*");

        $this->assertEmpty($filesAfter);
    }

    public function testGcRemovesOldFiles(): void
    {
        $handler = new FileSessionHandler($this->path);

        $file = "{$this->path}/sess_old";

        file_put_contents($file, '{}');

        touch($file, time() - 3600);

        $handler->gc(10);

        $this->assertFileDoesNotExist($file);
    }

    public function testConstructorThrowsWhenDirectoryCannotBeCreated(): void
    {
        $filePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'session_file_' . uniqid();
        file_put_contents($filePath, 'not a directory');

        try {
            $this->expectException(\RuntimeException::class);

            new FileSessionHandler($filePath);
        } finally {
            @unlink($filePath);
        }
    }
}
