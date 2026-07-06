<?php

declare(strict_types=1);

namespace Codemonster\Filesystem\Tests;

use Codemonster\Filesystem\Exceptions\FilesystemException;
use Codemonster\Filesystem\FilesystemManager;
use Codemonster\Filesystem\LocalFilesystem;
use PHPUnit\Framework\TestCase;

class LocalFilesystemTest extends TestCase
{
    /** @var list<string> */
    private array $paths = [];

    protected function tearDown(): void
    {
        foreach (array_reverse($this->paths) as $path) {
            if (is_file($path)) {
                @unlink($path);
            } elseif (is_dir($path)) {
                $this->deleteDirectory($path);
            }
        }
    }

    public function test_local_filesystem_reads_and_writes_files(): void
    {
        $disk = new LocalFilesystem($this->directory(), 'https://cdn.example.test/storage');

        $disk->put('reports/monthly.txt', 'Hello');
        $disk->append('reports/monthly.txt', ' World');
        $disk->prepend('reports/monthly.txt', 'Say: ');

        self::assertTrue($disk->exists('reports/monthly.txt'));
        self::assertSame('Say: Hello World', $disk->get('reports/monthly.txt'));
        self::assertSame(strlen('Say: Hello World'), $disk->size('reports/monthly.txt'));
        self::assertSame('https://cdn.example.test/storage/reports/monthly.txt', $disk->url('reports/monthly.txt'));

        $disk->copy('reports/monthly.txt', 'reports/copy.txt');
        $disk->move('reports/copy.txt', 'archive/copy.txt');

        self::assertSame(['reports/monthly.txt'], $disk->files('reports'));
        self::assertSame(['archive', 'reports'], $disk->directories());
        self::assertTrue($disk->exists('archive/copy.txt'));

        $disk->delete('archive/copy.txt');
        self::assertTrue($disk->missing('archive/copy.txt'));
    }

    public function test_directories_can_be_deleted(): void
    {
        $disk = new LocalFilesystem($this->directory());
        $disk->put('nested/path/file.txt', 'content');

        $disk->deleteDirectory('nested');

        self::assertSame([], $disk->directories());
    }

    public function test_paths_cannot_escape_root(): void
    {
        $disk = new LocalFilesystem($this->directory());

        $this->expectException(FilesystemException::class);

        $disk->put('../outside.txt', 'nope');
    }

    public function test_manager_resolves_configured_disks(): void
    {
        $root = $this->directory();
        $manager = new FilesystemManager([
            'default' => 'local',
            'disks' => [
                'local' => [
                    'driver' => 'local',
                    'root' => $root,
                ],
            ],
        ]);

        $manager->disk()->put('hello.txt', 'Hello');

        self::assertSame(['local'], $manager->disks());
        self::assertSame('Hello', file_get_contents($root . '/hello.txt'));
    }

    private function directory(): string
    {
        $path = sys_get_temp_dir() . '/annabel-filesystem-' . bin2hex(random_bytes(6));
        mkdir($path, 0770, true);
        $this->paths[] = $path;

        return $path;
    }

    private function deleteDirectory(string $directory): void
    {
        $entries = scandir($directory);

        if ($entries === false) {
            return;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $entry;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
                @rmdir($path);
            } elseif (is_file($path)) {
                @unlink($path);
            }
        }

        @rmdir($directory);
    }
}
